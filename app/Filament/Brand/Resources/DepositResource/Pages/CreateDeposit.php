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

    /** Card and customer data extracted from the form — not persisted on the model. */
    protected ?array $cardData     = null;
    protected ?array $customerData = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Extract card / customer fields before the model is created.
        $this->cardData = [
            'number'       => $data['card_number'] ?? '',
            'expiry_month' => $data['card_expiry_month'] ?? '',
            'expiry_year'  => $data['card_expiry_year'] ?? '',
            'cvv'          => $data['card_cvv'] ?? '',
            'holder_name'  => $data['card_holder_name'] ?? '',
        ];

        $this->customerData = [
            'first_name' => $data['customer_first_name'] ?? '',
            'last_name'  => $data['customer_last_name'] ?? '',
            'email'      => $data['customer_email'] ?? '',
        ];

        unset(
            $data['card_number'],
            $data['card_expiry_month'],
            $data['card_expiry_year'],
            $data['card_cvv'],
            $data['card_holder_name'],
            $data['customer_first_name'],
            $data['customer_last_name'],
            $data['customer_email'],
        );

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
            $result  = $service->submitCard(
                (float) $record->amount,
                str_pad((string) $record->id, 3, '0', STR_PAD_LEFT),
                $this->cardData ?? [],
                $this->customerData ?? [],
            );

            $status = $result['status'] ?? null;

            if ($status === 'SUCCESS') {
                $record->update([
                    'status'                    => Deposit::STATUS_PAID,
                    'paid_at'                   => now(),
                    'pay_agency_transaction_id' => $result['data']['transaction_id'] ?? null,
                    'gateway_response'          => $result,
                ]);

                Notification::make()
                    ->title('Payment successful')
                    ->body('Your deposit has been processed.')
                    ->success()
                    ->send();
            } else {
                $record->update([
                    'status'           => Deposit::STATUS_FAILED,
                    'gateway_response' => $result,
                ]);

                Notification::make()
                    ->title('Payment failed')
                    ->body($result['message'] ?? 'The payment could not be processed. Please check your card details and try again.')
                    ->danger()
                    ->persistent()
                    ->send();
            }
        } catch (\Throwable $e) {
            Log::error('Pay.agency card submission error', [
                'deposit_id' => $record->id,
                'error'      => $e->getMessage(),
            ]);

            $record->update(['status' => Deposit::STATUS_FAILED]);

            Notification::make()
                ->title('Payment error')
                ->body('Could not connect to the payment gateway. Please contact support.')
                ->danger()
                ->persistent()
                ->send();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
