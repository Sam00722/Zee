<?php

namespace App\Services\RunaPayment;

use App\Mail\RunaPaymentMail;
use App\Models\PaymentMethodAccount;
use App\Models\Withdrawal;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class RunaPaymentService
{
    protected string $apiUrl;
    protected string $apiKey;
    protected string $versionType;
    protected string $executionType;
    protected string $currency;
    protected string $accountBalanceType;
    protected string $distributionEmailType;
    protected string $productType;
    protected string $productValue;
    protected ?string $templateId;
    protected string $productionMode;
    protected ?string $description;

    public function __construct(PaymentMethodAccount $account)
    {
        $credentials = $account->credentials;
        $keys = is_array($credentials) ? $credentials : (array) json_decode($credentials, true);

        $this->apiKey = $keys['X_API_KEY'] ?? '';
        $baseUrl = $keys['BASE_URL'] ?? '';
        $this->apiUrl = rtrim((string) $baseUrl, '/');
        $this->versionType = $keys['X_API_VERSION'] ?? '';
        $this->executionType = $keys['X_Execution_Mode'] ?? '';
        $this->accountBalanceType = $keys['ACCOUNT_BALANCE'] ?? '';
        $this->currency = $keys['CURRENCY'] ?? 'USD';
        $this->distributionEmailType = $keys['EMAIL'] ?? 'email';
        $this->productType = $keys['SINGLE'] ?? '';
        $this->productValue = $keys['VALUE'] ?? '';
        $this->templateId = $keys['TEMPLATEID'] ?? null;
        $this->productionMode = $keys['PRODUCTION'] ?? 'false';
        $this->description = $keys['DESCRIPTION'] ?? null;
    }

    /**
     * Create Runa order for a withdrawal. Returns order response array or error array.
     */
    public function createOrder(Withdrawal $withdrawal): array
    {
        if ($this->apiUrl === '' || ! str_starts_with($this->apiUrl, 'http')) {
            throw new \InvalidArgumentException(
                'Runa BASE_URL is missing or invalid for this payment method account. Set BASE_URL in the account credentials (e.g. https://api.example.com).'
            );
        }

        $user = $withdrawal->user;
        $email = $user->email;
        $amount = (float) $withdrawal->amount;
        $username = $user->getFilamentName() ?: 'Customer';

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-Api-Key' => $this->apiKey,
            'X-Api-Version' => $this->versionType,
            'X-Execution-Mode' => $this->executionType,
            'X-Idempotency-Key' => (string) Str::uuid(),
        ])->post("{$this->apiUrl}/order", [
            'payment_method' => [
                'type' => $this->accountBalanceType,
                'currency' => $this->currency,
            ],
            'items' => [
                [
                    'face_value' => $amount,
                    'distribution_method' => [
                        'type' => $this->distributionEmailType,
                        'email_address' => $email,
                        'template_id' => $this->templateId,
                    ],
                    'products' => [
                        'type' => $this->productType,
                        'value' => $this->productValue,
                    ],
                ],
            ],
            'description' => $this->description ?? '',
        ]);

        $responseBody = $response->json();

        if ($response->successful() && isset($responseBody['id'])) {
            $orderId = $responseBody['id'];
            $orderDetails = $this->getOrderDetails($orderId);

            if (is_array($orderDetails)) {
                $status = $orderDetails['status'] ?? null;
                if ($status === 'COMPLETED' || $status === 'PROCESSING') {
                    $payoutUrl = null;
                    if ($this->productionMode === 'false') {
                        $payoutUrl = $this->getOrderRedemptionUrl($orderId);
                        $item = $orderDetails['items'][0] ?? [];
                        $totalPrice = $orderDetails['total_price'] ?? $amount;
                        if ($email && $payoutUrl) {
                            Mail::to($email)->queue(new RunaPaymentMail($email, $totalPrice, $payoutUrl));
                            $withdrawal->update(['is_email' => true]);
                        }
                    } else {
                        $payoutUrl = $orderDetails['items'][0]['redemption_url'] ?? null;
                    }
                    $orderDetails['redemption_url'] = $payoutUrl;
                    return $orderDetails;
                }
                return [
                    'message' => 'Your Runa order could not be completed at this time.',
                    'status' => $status,
                    'order_id' => $orderId,
                    'response' => $orderDetails,
                ];
            }
            return [
                'message' => 'Unexpected response while checking order status.',
                'response' => $orderDetails ?? null,
            ];
        }

        Log::error('Runa Order Creation Failed:', ['body' => $response->body()]);
        return [
            'message' => 'Order creation failed.',
            'response' => $response->body(),
        ];
    }

    public function getOrderDetails(string $orderId): ?array
    {
        $response = Http::withHeaders([
            'X-Api-Key' => $this->apiKey,
            'X-Api-Version' => $this->versionType,
        ])->get("{$this->apiUrl}/order/{$orderId}");

        return $response->successful() ? $response->json() : null;
    }

    private function getOrderRedemptionUrl(string $orderId): ?string
    {
        $url = "{$this->apiUrl}/order/{$orderId}";
        $maxAttempts = 10;
        $waitSeconds = 2;
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $response = Http::withHeaders([
                'X-Api-Key' => $this->apiKey,
                'X-Api-Version' => $this->versionType,
            ])->get($url);

            if ($response->successful()) {
                $order = $response->json();
                $status = $order['status'] ?? null;
                if ($status === 'COMPLETED') {
                    $item = $order['items'][0] ?? [];
                    return $item['redemption_url'] ?? null;
                }
                if ($status !== 'PROCESSING') {
                    break;
                }
            } else {
                break;
            }
            sleep($waitSeconds);
        }
        return null;
    }
}
