<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('deposits')) {
            return;
        }

        Schema::table('deposits', function (Blueprint $table) {
            if (! Schema::hasColumn('deposits', 'pay_agency_transaction_id')) {
                $table->string('pay_agency_transaction_id')->nullable()->after('paid_at');
            }
            if (! Schema::hasColumn('deposits', 'gateway_response')) {
                $table->json('gateway_response')->nullable()->after('pay_agency_transaction_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('deposits', function (Blueprint $table) {
            $table->dropColumn(['pay_agency_transaction_id', 'gateway_response']);
        });
    }
};
