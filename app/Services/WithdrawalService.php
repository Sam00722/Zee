<?php

namespace App\Services;

use App\Models\Withdrawal;
use App\Services\RunaPayment\RunaPaymentService;
use Illuminate\Support\Facades\Log;

class WithdrawalService
{
    /**
     * Approve withdrawal. For runa-payment PMA, creates Runa order and updates status from response.
     */
    public function approve(Withdrawal $withdrawal): array
    {
        $pma = $withdrawal->paymentMethodAccount;
        $type = $pma->payment_method_type ?? null;

        if ($type === 'runa-payment') {
            return $this->approveViaRuna($withdrawal);
        }

        $withdrawal->update([
            'status' => Withdrawal::STATUS_COMPLETED,
            'paid_at' => now(),
        ]);
        return ['success' => true, 'message' => 'Withdrawal approved.'];
    }

    protected function approveViaRuna(Withdrawal $withdrawal): array
    {
        try {
            $service = new RunaPaymentService($withdrawal->paymentMethodAccount);
            $response = $service->createOrder($withdrawal);
        } catch (\Throwable $e) {
            Log::error('Runa withdrawal failed: ' . $e->getMessage(), ['withdrawal_id' => $withdrawal->id]);
            $withdrawal->update([
                'status' => Withdrawal::STATUS_FAILED,
                'metadata' => ['error' => $e->getMessage()],
            ]);
            return ['success' => false, 'message' => $e->getMessage()];
        }

        $status = $response['status'] ?? null;
        $orderId = $response['id'] ?? $response['order_id'] ?? null;
        $message = $response['message'] ?? null;

        $redemptionUrl = $response['redemption_url'] ?? null;

        if ($orderId) {
            if (strtolower((string) $status) === 'completed') {
                $withdrawal->update([
                    'status' => Withdrawal::STATUS_COMPLETED,
                    'runa_order_id' => $orderId,
                    'sent_at' => now(),
                    'paid_at' => now(),
                    'metadata' => $response,
                ]);
                return [
                    'success' => true,
                    'message' => $redemptionUrl ? 'Withdrawal approved. Copy the claim link below to share with the customer.' : 'Withdrawal approved.',
                    'redemption_url' => $redemptionUrl,
                ];
            }
            if (strtolower((string) $status) === 'processing') {
                $withdrawal->update([
                    'status' => Withdrawal::STATUS_PROCESSING,
                    'runa_order_id' => $orderId,
                    'sent_at' => now(),
                    'metadata' => $response,
                ]);
                return [
                    'success' => true,
                    'message' => $redemptionUrl ? 'Withdrawal is processing. Copy the claim link below to share with the customer.' : 'Withdrawal is processing.',
                    'redemption_url' => $redemptionUrl,
                ];
            }
            if (strtolower((string) $status) === 'failed') {
                $withdrawal->update([
                    'status' => Withdrawal::STATUS_FAILED,
                    'runa_order_id' => $orderId,
                    'metadata' => $response,
                ]);
                return ['success' => false, 'message' => $message ?? 'Runa order failed.'];
            }
        }

        $withdrawal->update([
            'status' => Withdrawal::STATUS_FAILED,
            'metadata' => $response,
        ]);
        return ['success' => false, 'message' => $message ?? 'Unknown Runa response.'];
    }

    public function deny(Withdrawal $withdrawal): void
    {
        $withdrawal->update(['status' => Withdrawal::STATUS_DENIED]);
    }
}
