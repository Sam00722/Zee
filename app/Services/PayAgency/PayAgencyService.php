<?php

namespace App\Services\PayAgency;

use App\Models\PaymentMethodAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayAgencyService
{
    protected string $bearerToken;

    protected string $templateId;

    protected string $currency;

    protected string $webhookSecret;

    protected string $statusApiUrl;

    private const PAYMENT_LINK_URL = 'https://backend.pay.agency/api/v1/payment-link';

    public function __construct(PaymentMethodAccount $account)
    {
        $credentials = is_array($account->credentials)
            ? $account->credentials
            : (array) json_decode((string) $account->credentials, true);

        $this->bearerToken   = $credentials['bearer_token'] ?? '';
        $this->templateId    = $credentials['payment_template_id'] ?? '';
        $this->currency      = $credentials['currency'] ?? 'USD';
        $this->webhookSecret = $credentials['webhook_secret'] ?? '';

        $mode = $credentials['mode'] ?? 'live';
        $this->statusApiUrl = $mode === 'test'
            ? 'https://backend.pay.agency/api/v1/test/card'
            : 'https://backend.pay.agency/api/v1/live/card';
    }

    /**
     * Create a hosted payment link on pay.agency.
     * Returns the raw JSON response. On success, response['data'] is the payment URL.
     */
    public function createPaymentLink(float $amount, string $orderId): array
    {
        if ($this->bearerToken === '') {
            throw new \InvalidArgumentException(
                'Pay.agency bearer_token must be set in the account credentials.'
            );
        }

        // payment_template_id and terminal_id must NOT be sent with a test key (pay.agency docs).
        // Only include them when they are explicitly set in the credentials (i.e. live mode).
        $payload = array_filter([
            'payment_template_id' => $this->templateId ?: null,
            'amount'              => $amount,
            'currency'            => $this->currency ?: null,
            'order_id'            => $orderId,
        ], static fn ($v) => $v !== null);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->bearerToken,
            'Content-Type'  => 'application/json',
        ])->post(self::PAYMENT_LINK_URL, $payload);

        $body = $response->json();

        if ($body === null) {
            Log::error('Pay.agency: empty response from payment link API', [
                'http_status' => $response->status(),
                'body'        => $response->body(),
            ]);

            return ['message' => 'No response received from gateway.'];
        }

        return $body;
    }

    /**
     * Verify the fs-webhook-hash header sent by pay.agency.
     * Returns true if webhook_secret is not configured (verification skipped).
     */
    public function verifyWebhookSignature(string $rawPayload, string $hash): bool
    {
        if ($this->webhookSecret === '') {
            return true;
        }

        return hash_equals(
            hash_hmac('sha256', $rawPayload, $this->webhookSecret),
            $hash
        );
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * Look up a transaction status by transaction ID via the status API.
     */
    public function getTransactionStatus(string $transactionId): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->bearerToken,
            'Content-Type'  => 'application/json',
        ])->get("{$this->statusApiUrl}/status/{$transactionId}");

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
