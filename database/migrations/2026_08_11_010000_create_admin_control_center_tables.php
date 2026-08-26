<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('branches')) {
            Schema::create('branches', function (Blueprint $table) {
                $table->id();
                $table->string('code', 40)->unique();
                $table->string('name');
                $table->string('phone', 30)->nullable();
                $table->string('address')->nullable();
                $table->string('manager_name')->nullable();
                $table->boolean('is_main')->default(false);
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('gym_periods')) {
            Schema::create('gym_periods', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
                $table->string('name');
                $table->string('gender', 10);
                $table->unsignedTinyInteger('slot_order')->default(1);
                $table->time('start_time')->nullable();
                $table->time('end_time')->nullable();
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->unique(['branch_id', 'gender', 'slot_order'], 'gym_periods_branch_gender_slot_unique');
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (! Schema::hasColumn('users', 'branch_id')) {
                    $table->foreignId('branch_id')->nullable()->after('work_period')->constrained('branches')->nullOnDelete();
                }
                if (! Schema::hasColumn('users', 'gym_period_id')) {
                    $table->foreignId('gym_period_id')->nullable()->after('branch_id')->constrained('gym_periods')->nullOnDelete();
                }
            });
        }

        if (Schema::hasTable('members')) {
            Schema::table('members', function (Blueprint $table) {
                if (! Schema::hasColumn('members', 'branch_id')) {
                    $table->foreignId('branch_id')->nullable()->after('assigned_period')->constrained('branches')->nullOnDelete();
                }
                if (! Schema::hasColumn('members', 'gym_period_id')) {
                    $table->foreignId('gym_period_id')->nullable()->after('branch_id')->constrained('gym_periods')->nullOnDelete();
                }
            });
        }

        $now = now();
        $mainBranchId = DB::table('branches')->where('is_main', true)->value('id');
        if (! $mainBranchId) {
            $mainBranchId = DB::table('branches')->insertGetId([
                'code' => 'MAIN',
                'name' => 'الفرع الرئيسي',
                'address' => 'المكلا، اليمن',
                'is_main' => true,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $defaults = [
            ['name' => 'رجال - الفترة الأولى', 'gender' => 'men', 'slot_order' => 1],
            ['name' => 'رجال - الفترة الثانية', 'gender' => 'men', 'slot_order' => 2],
            ['name' => 'نساء - الفترة الأولى', 'gender' => 'women', 'slot_order' => 1],
            ['name' => 'نساء - الفترة الثانية', 'gender' => 'women', 'slot_order' => 2],
        ];

        foreach ($defaults as $period) {
            DB::table('gym_periods')->updateOrInsert(
                ['branch_id' => $mainBranchId, 'gender' => $period['gender'], 'slot_order' => $period['slot_order']],
                [
                    'name' => $period['name'],
                    'is_active' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }

        DB::table('users')->whereNull('branch_id')->update(['branch_id' => $mainBranchId]);
        DB::table('members')->whereNull('branch_id')->update(['branch_id' => $mainBranchId]);
    }

    public function down(): void
    {
        if (Schema::hasTable('members')) {
            Schema::table('members', function (Blueprint $table) {
                if (Schema::hasColumn('members', 'gym_period_id')) {
                    $table->dropConstrainedForeignId('gym_period_id');
                }
                if (Schema::hasColumn('members', 'branch_id')) {
                    $table->dropConstrainedForeignId('branch_id');
                }
            });
        }
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'gym_period_id')) {
                    $table->dropConstrainedForeignId('gym_period_id');
                }
                if (Schema::hasColumn('users', 'branch_id')) {
                    $table->dropConstrainedForeignId('branch_id');
                }
            });
        }
        Schema::dropIfExists('gym_periods');
        Schema::dropIfExists('branches');
    }
};
