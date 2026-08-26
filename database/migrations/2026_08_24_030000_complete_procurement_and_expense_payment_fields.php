<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('purchases')) {
            Schema::table('purchases', function (Blueprint $table): void {
                if (! Schema::hasColumn('purchases', 'transfer_service')) {
                    $table->string('transfer_service', 80)->nullable()->after('payment_method');
                }
                if (! Schema::hasColumn('purchases', 'proof_path')) {
                    $table->string('proof_path')->nullable()->after('transfer_reference');
                }
            });
        }

        if (Schema::hasTable('expenses') && ! Schema::hasColumn('expenses', 'transfer_service')) {
            Schema::table('expenses', function (Blueprint $table): void {
                $table->string('transfer_service', 80)->nullable()->after('payment_method');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('purchases')) {
            Schema::table('purchases', function (Blueprint $table): void {
                $columns = array_values(array_filter(
                    ['transfer_service', 'proof_path'],
                    fn (string $column): bool => Schema::hasColumn('purchases', $column),
                ));
                if ($columns !== []) {
                    $table->dropColumn($columns);
                }
            });
        }

        if (Schema::hasTable('expenses') && Schema::hasColumn('expenses', 'transfer_service')) {
            Schema::table('expenses', function (Blueprint $table): void {
                $table->dropColumn('transfer_service');
            });
        }
    }
};
