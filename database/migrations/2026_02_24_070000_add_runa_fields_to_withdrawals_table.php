<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('withdrawals') || Schema::hasColumn('withdrawals', 'runa_order_id')) {
            return;
        }

        Schema::table('withdrawals', function (Blueprint $table) {
            $table->json('metadata')->nullable()->after('notes');
            $table->string('runa_order_id')->nullable()->after('metadata');
            $table->timestamp('sent_at')->nullable()->after('paid_at');
            $table->boolean('is_email')->default(false)->after('sent_at');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('withdrawals') || ! Schema::hasColumn('withdrawals', 'runa_order_id')) {
            return;
        }

        Schema::table('withdrawals', function (Blueprint $table) {
            $table->dropColumn(['metadata', 'runa_order_id', 'sent_at', 'is_email']);
        });
    }
};
