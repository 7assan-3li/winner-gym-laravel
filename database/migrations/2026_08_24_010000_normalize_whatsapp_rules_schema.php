<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('whatsapp_rules')) {
            return;
        }

        $legacyColumnsExist = Schema::hasColumn('whatsapp_rules', 'trigger')
            && Schema::hasColumn('whatsapp_rules', 'delay_days')
            && Schema::hasColumn('whatsapp_rules', 'target_group');

        if (! $legacyColumnsExist) {
            return;
        }

        DB::table('whatsapp_rules')
            ->select(['id', 'trigger', 'delay_days', 'target_group'])
            ->orderBy('id')
            ->chunkById(100, function ($rules): void {
                foreach ($rules as $rule) {
                    $updates = [];

                    if (in_array($rule->trigger, ['near_expiry', 'expired', 'reactivation'], true)) {
                        $updates['type'] = $rule->trigger;
                    }

                    if ($rule->delay_days !== null) {
                        $updates['days_offset'] = max(0, (int) $rule->delay_days);
                    }

                    if (in_array($rule->target_group, ['all', 'men', 'women'], true)) {
                        $updates['audience'] = $rule->target_group;
                    }

                    if ($updates !== []) {
                        DB::table('whatsapp_rules')->where('id', $rule->id)->update($updates);
                    }
                }
            });
    }

    public function down(): void
    {
        // Data normalization is intentionally not reversed.
    }
};
