<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            ['name' => 'Stripe', 'type' => 'deposit', 'enabled' => true],
            ['name' => 'Checkbook.io', 'type' => 'withdrawal', 'enabled' => true],
        ];

        foreach ($methods as $method) {
            PaymentMethod::firstOrCreate(
                ['name' => $method['name'], 'type' => $method['type']],
                array_merge($method, ['created_at' => now(), 'updated_at' => now()])
            );
        }
    }
}
