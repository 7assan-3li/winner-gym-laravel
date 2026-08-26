<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('whatsapp_rules')) {
            Schema::table('whatsapp_rules', function (Blueprint $table) {
                if (! Schema::hasColumn('whatsapp_rules', 'name')) {
                    $table->string('name')->nullable();
                }
                if (! Schema::hasColumn('whatsapp_rules', 'trigger')) {
                    $table->string('trigger')->nullable();
                }
                if (! Schema::hasColumn('whatsapp_rules', 'delay_days')) {
                    $table->unsignedInteger('delay_days')->default(0);
                }
                if (! Schema::hasColumn('whatsapp_rules', 'message_template')) {
                    $table->text('message_template')->nullable();
                }
                if (! Schema::hasColumn('whatsapp_rules', 'template_name')) {
                    $table->string('template_name')->nullable();
                }
                if (! Schema::hasColumn('whatsapp_rules', 'template_language')) {
                    $table->string('template_language')->default('ar');
                }
                if (! Schema::hasColumn('whatsapp_rules', 'target_group')) {
                    $table->string('target_group')->default('all');
                }
                if (! Schema::hasColumn('whatsapp_rules', 'mode')) {
                    $table->string('mode')->default('manual');
                }
                if (! Schema::hasColumn('whatsapp_rules', 'is_enabled')) {
                    $table->boolean('is_enabled')->default(false);
                }
                if (! Schema::hasColumn('whatsapp_rules', 'last_run_at')) {
                    $table->timestampTz('last_run_at')->nullable();
                }
                if (! Schema::hasColumn('whatsapp_rules', 'created_by')) {
                    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                }
            });
        }

        if (Schema::hasTable('whatsapp_messages')) {
            Schema::table('whatsapp_messages', function (Blueprint $table) {
                if (! Schema::hasColumn('whatsapp_messages', 'rule_id')) {
                    $table->foreignId('rule_id')->nullable()->constrained('whatsapp_rules')->nullOnDelete();
                }
                if (! Schema::hasColumn('whatsapp_messages', 'member_id')) {
                    $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();
                }
                if (! Schema::hasColumn('whatsapp_messages', 'phone')) {
                    $table->string('phone')->nullable();
                }
                if (! Schema::hasColumn('whatsapp_messages', 'message')) {
                    $table->text('message')->nullable();
                }
                if (! Schema::hasColumn('whatsapp_messages', 'status')) {
                    $table->string('status')->default('pending');
                }
                if (! Schema::hasColumn('whatsapp_messages', 'mode')) {
                    $table->string('mode')->default('manual');
                }
                if (! Schema::hasColumn('whatsapp_messages', 'provider_message_id')) {
                    $table->string('provider_message_id')->nullable();
                }
                if (! Schema::hasColumn('whatsapp_messages', 'error_message')) {
                    $table->text('error_message')->nullable();
                }
                if (! Schema::hasColumn('whatsapp_messages', 'sent_by')) {
                    $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
                }
                if (! Schema::hasColumn('whatsapp_messages', 'sent_at')) {
                    $table->timestampTz('sent_at')->nullable();
                }
            });
        }

        if (Schema::hasTable('backup_logs')) {
            Schema::table('backup_logs', function (Blueprint $table) {
                if (! Schema::hasColumn('backup_logs', 'filename')) {
                    $table->string('filename')->nullable();
                }
                if (! Schema::hasColumn('backup_logs', 'disk')) {
                    $table->string('disk')->default('local');
                }
                if (! Schema::hasColumn('backup_logs', 'path')) {
                    $table->string('path')->nullable();
                }
                if (! Schema::hasColumn('backup_logs', 'size_bytes')) {
                    $table->unsignedBigInteger('size_bytes')->nullable();
                }
                if (! Schema::hasColumn('backup_logs', 'status')) {
                    $table->string('status')->default('completed');
                }
                if (! Schema::hasColumn('backup_logs', 'created_by')) {
                    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                }
                if (! Schema::hasColumn('backup_logs', 'restored_by')) {
                    $table->foreignId('restored_by')->nullable()->constrained('users')->nullOnDelete();
                }
                if (! Schema::hasColumn('backup_logs', 'restored_at')) {
                    $table->timestampTz('restored_at')->nullable();
                }
                if (! Schema::hasColumn('backup_logs', 'error_message')) {
                    $table->text('error_message')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        // Intentionally non-destructive. Runtime compatibility fields are retained.
    }
};
