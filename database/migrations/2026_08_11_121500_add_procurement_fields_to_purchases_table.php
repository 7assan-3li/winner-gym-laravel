<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->string('supplier_name')->nullable();
            $table->string('supplier_invoice', 120)->nullable();
            $table->string('payment_method', 20)->nullable();
            $table->string('transfer_reference')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn(['supplier_name', 'supplier_invoice', 'payment_method', 'transfer_reference']);
        });
    }
};
