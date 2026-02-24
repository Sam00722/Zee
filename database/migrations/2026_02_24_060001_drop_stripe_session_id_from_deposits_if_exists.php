<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('deposits', 'stripe_checkout_session_id')) {
            Schema::table('deposits', function (Blueprint $table) {
                $table->dropColumn('stripe_checkout_session_id');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('deposits', 'stripe_checkout_session_id')) {
            Schema::table('deposits', function (Blueprint $table) {
                $table->string('stripe_checkout_session_id')->nullable()->after('notes');
            });
        }
    }
};
