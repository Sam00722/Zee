<?php

namespace App\Services\PayAgency;

use App\Models\PaymentMethodAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayAgencyService
{
    protected string $bearerToken;

    protected string $currency;

    /** Base URL for card charge and status endpoints. */
    protected string $cardApiUrl;

    public function __construct(PaymentMethodAccount $account)
    {
        $credentials = is_array($account->credentials)
            ? $account->credentials
            : (array) json_decode((string) $account->credentials, true);

        $this->bearerToken = $credentials['bearer_token'] ?? '';
        $this->currency    = $credentials['currency'] ?? 'USD';

        $mode = $credentials['mode'] ?? 'live';
        $this->cardApiUrl = $mode === 'test'
            ? 'https://backend.pay.agency/api/v1/test/card'
            : 'https://backend.pay.agency/api/v1/live/card';
    }

    /**
     * Submit a card payment directly to pay.agency.
     *
     * @param  float   $amount
     * @param  string  $orderId
     * @param  array   $card     Keys: number, expiry_month, expiry_year, cvv, holder_name
     * @param  array   $customer Keys: first_name, last_name, email
     */
    public function submitCard(float $amount, string $orderId, array $card, array $customer): array
    {
        if ($this->bearerToken === '') {
            throw new \InvalidArgumentException('Pay.agency bearer_token must be set in the account credentials.');
        }

        $payload = [
            'amount'   => $amount,
            'currency' => $this->currency,
            'order_id' => $orderId,
            'card'     => [
                'number'       => preg_replace('/\D/', '', $card['number'] ?? ''),
                'expiry_month' => $card['expiry_month'] ?? '',
                'expiry_year'  => $card['expiry_year'] ?? '',
                'cvv'          => $card['cvv'] ?? '',
                'holder_name'  => $card['holder_name'] ?? '',
            ],
            'customer' => [
                'first_name' => $customer['first_name'] ?? '',
                'last_name'  => $customer['last_name'] ?? '',
                'email'      => $customer['email'] ?? '',
            ],
        ];

        Log::info('Pay.agency: submitting card payment', [
            'url'        => $this->cardApiUrl,
            'order_id'   => $orderId,
            'amount'     => $amount,
            'currency'   => $this->currency,
            'card_last4' => substr(preg_replace('/\D/', '', $card['number'] ?? ''), -4),
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
