<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('appointment_payments') && ! Schema::hasColumn('appointment_payments', 'transfer_service')) {
            Schema::table('appointment_payments', function (Blueprint $table): void {
                $table->string('transfer_service', 80)->nullable()->after('payment_method');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('appointment_payments') && Schema::hasColumn('appointment_payments', 'transfer_service')) {
            Schema::table('appointment_payments', function (Blueprint $table): void {
                $table->dropColumn('transfer_service');
            });
        }
    }
};
