<?php

namespace App\Filament\Brand\Resources\DepositResource\Pages;

use App\Enums\PaymentMethodType;
use App\Filament\Brand\Resources\DepositResource;
use App\Models\Deposit;
use App\Services\PayAgency\PayAgencyService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;

class CreateDeposit extends CreateRecord
{
    protected static string $resource = DepositResource::class;

    /** Payment link URL returned by pay.agency — used to redirect the user after creation. */
    protected ?string $payAgencyPaymentLinkUrl = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
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

        try {
            $service = new PayAgencyService($account);
            $result  = $service->createPaymentLink(
                (float) $record->amount,
                str_pad((string) $record->id, 3, '0', STR_PAD_LEFT)
            );

            $paymentUrl = $result['data'] ?? null;

            if ($paymentUrl && str_starts_with((string) $paymentUrl, 'http')) {
                // Store on record and redirect user to pay.agency's hosted payment page
                $record->update(['gateway_response' => $result]);
                $this->payAgencyPaymentLinkUrl = $paymentUrl;
            } else {
                $record->update([
                    'status'           => Deposit::STATUS_FAILED,
                    'gateway_response' => $result,
                ]);

                Notification::make()
                    ->title('Could not generate payment link')
                    ->body($result['message'] ?? 'Please try again or contact support.')
                    ->danger()
                    ->persistent()
                    ->send();
            }
        } catch (\Throwable $e) {
            Log::error('Pay.agency payment link error', [
                'deposit_id' => $record->id,
                'error'      => $e->getMessage(),
            ]);

            $record->update(['status' => Deposit::STATUS_FAILED]);

            Notification::make()
                ->title('Payment error')
                ->body('Could not connect to payment gateway. Please contact support.')
                ->danger()
                ->persistent()
                ->send();
        }
    }

    /**
     * Redirect to the pay.agency payment page if a link was generated,
     * otherwise fall back to the deposits list.
     */
    protected function getRedirectUrl(): string
    {
        return $this->payAgencyPaymentLinkUrl ?? $this->getResource()::getUrl('index');
    }
}
