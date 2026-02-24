<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ZeroSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolesSeeder::class);
        $this->call(PaymentMethodSeeder::class);

        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@zero.test'],
            [
                'name' => 'Super Admin',
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'password' => Hash::make('password'),
            ]
        );
        $superAdmin->assignRole('Super Admin');

        $brand = Brand::firstOrCreate(
            ['slug' => 'demo-brand'],
            [
                'name' => 'Demo Brand',
                'timezone' => 'America/Chicago',
                'can_deposit' => true,
                'can_withdraw' => true,
            ]
        );

        $brandUser = User::firstOrCreate(
            ['email' => 'brand@zero.test'],
            [
                'name' => 'Brand User',
                'first_name' => 'Brand',
                'last_name' => 'User',
                'password' => Hash::make('password'),
            ]
        );
        $brandUser->assignRole('Brand');
        if (! $brandUser->brands()->where('brand_id', $brand->id)->exists()) {
            $brandUser->brands()->attach($brand->id, ['role' => 'admin']);
        }
    }
}
