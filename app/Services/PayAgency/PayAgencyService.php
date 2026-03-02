<?php

namespace App\Services\PayAgency;

use App\Models\PaymentMethodAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayAgencyService
{
    protected string $bearerToken;

    protected string $currency;

    protected string $terminalId;

    /** Base URL for card charge and status endpoints. */
    protected string $cardApiUrl;

    public function __construct(PaymentMethodAccount $account)
    {
        $credentials = is_array($account->credentials)
            ? $account->credentials
            : (array) json_decode((string) $account->credentials, true);

        $this->bearerToken = $credentials['bearer_token'] ?? '';
        $this->currency    = $credentials['currency'] ?? 'USD';
        $this->terminalId  = $credentials['terminal_id'] ?? '';

        $mode = $credentials['mode'] ?? 'live';
        $this->cardApiUrl = $mode === 'test'
            ? 'https://backend.pay.agency/api/v1/test/card'
            : 'https://backend.pay.agency/api/v1/live/card';
    }

    /**
     * Submit a card payment directly to pay.agency.
     *
     * Expected payload fields (all flat, no nesting):
     *   first_name, last_name, email, phone_number
     *   address, city, state, zip, country (ISO 2-letter)
     *   card_number, card_expiry_month, card_expiry_year, card_cvv
     *   amount, currency, order_id
     *   ip_address, redirect_url, webhook_url
     */
    public function submitCard(float $amount, string $orderId, array $data): array
    {
        if ($this->bearerToken === '') {
            throw new \InvalidArgumentException('Pay.agency bearer_token must be set in the account credentials.');
        }

        $payload = [
            // Customer
            'first_name'    => $data['customer_first_name'] ?? '',
            'last_name'     => $data['customer_last_name'] ?? '',
            'email'         => $data['customer_email'] ?? '',
            'phone_number'  => $data['phone_number'] ?? '',

            // Billing address
            'address'       => $data['address'] ?? '',
            'city'          => $data['city'] ?? '',
            'state'         => $data['state'] ?? '',
            'zip'           => $data['zip'] ?? '',
            'country'       => $data['country'] ?? '',

            // Card
            'card_number'       => preg_replace('/\D/', '', $data['card_number'] ?? ''),
            'card_expiry_month' => $data['card_expiry_month'] ?? '',
            'card_expiry_year'  => $data['card_expiry_year'] ?? '',
            'card_cvv'          => $data['card_cvv'] ?? '',

            // Transaction
            'amount'        => $amount,
            'currency'      => $this->currency,
            'order_id'      => $orderId,
            'ip_address'    => $data['ip_address'] ?? '',
            'redirect_url'  => $data['redirect_url'] ?? '',
            'webhook_url'   => $data['webhook_url'] ?? '',
            'terminal_id'   => $this->terminalId ?: null,
        ];

        // Remove null/empty optional fields so the API doesn't reject them.
        $payload = array_filter($payload, fn ($v) => $v !== null && $v !== '');

        Log::info('Pay.agency: submitting card payment', [
            'url'        => $this->cardApiUrl,
            'order_id'   => $orderId,
            'amount'     => $amount,
            'currency'   => $this->currency,
            'card_last4' => substr(preg_replace('/\D/', '', $data['card_number'] ?? ''), -4),
        ]);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->bearerToken,
            'Content-Type'  => 'application/json',
        ])->post($this->cardApiUrl, $payload);

        $body = $response->json();

        Log::info('Pay.agency: card payment response', [
            'http_status'   => $response->status(),
            'response_body' => $body ?? $response->body(),
        ]);

        if ($body === null) {
            Log::error('Pay.agency: empty response from card API', [
                'http_status' => $response->status(),
                'body'        => $response->body(),
            ]);

            return ['status' => 'ERROR', 'message' => 'No response received from gateway.'];
        }

        return $body;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * Look up a transaction status by transaction ID.
     */
    public function getTransactionStatus(string $transactionId): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->bearerToken,
            'Content-Type'  => 'application/json',
        ])->get("{$this->cardApiUrl}/status/{$transactionId}");

        $body = $response->json();

        if ($body === null) {
            Log::warning('Pay.agency: could not retrieve transaction status', [
                'transaction_id' => $transactionId,
                'http_status'    => $response->status(),
            ]);

            return ['status' => 'UNKNOWN', 'message' => 'Could not retrieve transaction status.'];
        }

        return $body;
    }
}
