<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        foreach ([
            ['code' => 'muscle_mass', 'name_ar' => 'كتلة العضلات', 'unit' => 'kg', 'sort_order' => 65],
            ['code' => 'hip', 'name_ar' => 'محيط الورك', 'unit' => 'cm', 'sort_order' => 105],
        ] as $type) {
            DB::table('measurement_types')->updateOrInsert(
                ['code' => $type['code']],
                [
                    'name_ar' => $type['name_ar'],
                    'unit' => $type['unit'],
                    'is_active' => true,
                    'sort_order' => $type['sort_order'],
                    'updated_at' => $now,
                    'created_at' => $now,
                ],
            );
        }
    }

    public function down(): void
    {
        // Data types may already be referenced by measurement_values.
        // Keep them on rollback to avoid destructive loss of nutrition history.
    }
};
