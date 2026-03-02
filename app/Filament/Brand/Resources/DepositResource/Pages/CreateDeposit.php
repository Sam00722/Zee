<?php

namespace App\Filament\Brand\Resources\DepositResource\Pages;

use App\Enums\PaymentMethodType;
use App\Filament\Brand\Resources\DepositResource;
use App\Models\Deposit;
use App\Services\PayAgency\PayAgencyService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;

class CreateDeposit extends CreateRecord
{
    protected static string $resource = DepositResource::class;

    /** Flat card + billing + customer data extracted from the form — not persisted on the model. */
    protected array $paymentData = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Extract all payment-related fields (not stored on the Deposit model).
        $paymentKeys = [
            'card_number', 'card_expiry_month', 'card_expiry_year', 'card_cvv',
            'customer_first_name', 'customer_last_name', 'customer_email', 'phone_number',
            'address', 'city', 'state', 'zip', 'country',
        ];

        foreach ($paymentKeys as $key) {
            $this->paymentData[$key] = $data[$key] ?? '';
            unset($data[$key]);
        }

        $brand = auth()->user()->brands()->first();
        $data['brand_id'] = $brand->id;
        $data['user_id']  = auth()->id();
        $data['status']   = Deposit::STATUS_PENDING;

        return $data;
    }

    protected function afterCreate(): void
    {
        $record  = $this->record;
        $account = $record->paymentMethodAccount;

        if ($account?->payment_method_type !== PaymentMethodType::PAYAGENCY->value) {
            return;
        }

        // Add server-side fields not collected from the form.
        $this->paymentData['ip_address']   = request()->ip() ?? '127.0.0.1';
        $this->paymentData['redirect_url'] = url('/company/deposits');
        $this->paymentData['webhook_url']  = '';

        try {
            $service = new PayAgencyService($account);
            $result  = $service->submitCard(
                (float) $record->amount,
                str_pad((string) $record->id, 3, '0', STR_PAD_LEFT),
                $this->paymentData,
            );

            $status = $result['status'] ?? null;

            if ($status === 'SUCCESS') {
                $record->update([
                    'status'                    => Deposit::STATUS_PAID,
                    'paid_at'                   => now(),
                    'pay_agency_transaction_id' => $result['data']['transaction_id'] ?? null,
                    'gateway_response'          => $result,
                ]);
            } elseif ($status === 'REDIRECT') {
                // 3DS challenge triggered — not supported in this integration.
                $record->update([
                    'status'           => Deposit::STATUS_FAILED,
                    'gateway_response' => $result,
                ]);
            } else {
                $record->update([
                    'status'           => Deposit::STATUS_FAILED,
                    'gateway_response' => $result,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Pay.agency card submission error', [
                'deposit_id' => $record->id,
                'error'      => $e->getMessage(),
            ]);

            $record->update(['status' => Deposit::STATUS_FAILED]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
