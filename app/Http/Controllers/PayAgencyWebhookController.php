<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethodType;
use App\Models\Deposit;
use App\Services\PayAgency\PayAgencyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PayAgencyWebhookController extends Controller
{
    /**
     * Handle a webhook POST from pay.agency.
     *
     * Triggered when a payment reaches a final status: SUCCESS, FAILED, or BLOCKED.
     * Retried up to 3 times by pay.agency if we return a non-2xx response.
     *
     * Expected payload:
     * {
     *   "status": "SUCCESS",
     *   "message": "Transaction processed successfully!",
     *   "data": {
     *     "amount": 100,
     *     "currency": "GBP",
     *     "order_id": "123",
     *     "transaction_id": "PA766...",
     *     "customer": { "first_name": "...", "last_name": "...", "email": "..." },
     *     "refund": { "status": false, "refund_date": null },
     *     "chargeback": { "status": false, "chargeback_date": null }
     *   }
     * }
     *
     * Configure this URL in your pay.agency payment template:
     *   https://yourdomain.com/pay-agency/webhook
     */
    public function handle(Request $request): JsonResponse
    {
        $rawPayload = $request->getContent();
        $payload    = $request->all();

        Log::info('Pay.agency webhook received', ['payload' => $payload]);

        $status        = $payload['status'] ?? null;
        $data          = $payload['data'] ?? [];
        $orderId       = $data['order_id'] ?? null;
        $transactionId = $data['transaction_id'] ?? null;

        if (! $orderId) {
            Log::warning('Pay.agency webhook: missing order_id', ['payload' => $payload]);

            return response()->json(['message' => 'Missing order_id'], 400);
        }

        $deposit = Deposit::find($orderId);

        if (! $deposit) {
            Log::warning('Pay.agency webhook: deposit not found', ['order_id' => $orderId]);

            return response()->json(['message' => 'Deposit not found'], 404);
        }

        // Verify webhook signature if webhook_secret is configured
        $account = $deposit->paymentMethodAccount;

        if ($account?->payment_method_type === PaymentMethodType::PAYAGENCY->value) {
            $hash = $request->header('fs-webhook-hash', '');

            if ($hash !== '') {
                $service = new PayAgencyService($account);

                if (! $service->verifyWebhookSignature($rawPayload, $hash)) {
                    Log::warning('Pay.agency webhook: invalid signature', ['order_id' => $orderId]);

                    return response()->json(['message' => 'Invalid signature'], 401);
                }
            }
        }

        // Skip if already in a final state (idempotency)
        if ($deposit->status !== Deposit::STATUS_PENDING) {
            return response()->json(['message' => 'Already processed']);
        }

        if ($status === 'SUCCESS') {
            $deposit->update([
                'status'                    => Deposit::STATUS_PAID,
                'paid_at'                   => now(),
                'pay_agency_transaction_id' => $transactionId,
                'gateway_response'          => $payload,
            ]);

            Log::info('Pay.agency webhook: deposit marked paid', [
                'deposit_id'     => $deposit->id,
                'transaction_id' => $transactionId,
            ]);
        } else {
            // FAILED or BLOCKED
            $deposit->update([
                'status'                    => Deposit::STATUS_FAILED,
                'pay_agency_transaction_id' => $transactionId,
                'gateway_response'          => $payload,
            ]);

            Log::info('Pay.agency webhook: deposit marked failed', [
                'deposit_id' => $deposit->id,
                'status'     => $status,
            ]);
        }

        return response()->json(['message' => 'OK']);
    }
}
