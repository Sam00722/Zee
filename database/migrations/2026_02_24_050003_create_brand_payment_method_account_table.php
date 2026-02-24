<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('brand_payment_method_account')) {
            return;
        }

        Schema::create('brand_payment_method_account', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_method_account_id')->constrained()->cascadeOnDelete();
            $table->boolean('enable')->default(true);
            $table->timestamps();

            $table->unique(['brand_id', 'payment_method_account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brand_payment_method_account');
    }
};
