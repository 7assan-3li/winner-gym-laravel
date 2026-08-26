<?php

namespace App\Console\Commands;

use App\Models\RolePermission;
use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ListOrResetStaff extends Command
{
    protected $signature = 'winner-gym:seed-demo-staff';

    protected $description = 'Clean and standardize all staff roles and permissions';

    public function handle(): int
    {
        $defaultPassword = 'WinnerGym@2026';

        $cleanStaff = [
            ['username' => 'reception', 'name' => 'موظف الاستقبال', 'role' => 'reception', 'email' => 'reception.staff@winnergym.local'],
            ['username' => 'reception_tester', 'name' => 'موظف استقبال تجريبي', 'role' => 'reception', 'email' => 'reception.tester@winnergym.local'],
            ['username' => 'accountant', 'name' => 'المحاسب المالي', 'role' => 'accountant', 'email' => 'accountant.staff@winnergym.local'],
            ['username' => 'emp1', 'name' => 'محاسب تجريبي', 'role' => 'accountant', 'email' => 'emp1@winnergym.local'],
            ['username' => 'nutritionist', 'name' => 'أخصائي التغذية', 'role' => 'nutritionist', 'email' => 'nutritionist.staff@winnergym.local'],
            ['username' => 'dr_nutrition', 'name' => 'د. سالم أخصائي التغذية', 'role' => 'nutritionist', 'email' => 'dr_nutrition@winnergym.local'],
            ['username' => 'manager', 'name' => 'مدير النادي', 'role' => 'manager', 'email' => 'manager.staff@winnergym.local'],
            ['username' => 'admin', 'name' => 'المالك العام', 'role' => 'owner', 'email' => 'admin@winnergym.local'],
            ['username' => 'owner', 'name' => 'مالك النادي', 'role' => 'owner', 'email' => 'owner@winnergym.local'],
        ];

        foreach ($cleanStaff as $s) {
            $user = User::firstOrNew(['username' => $s['username']]);
            $user->name = $s['name'];
            $user->role = $s['role'];
            $user->email = $s['email'];
            $user->password = Hash::make($defaultPassword);
            $user->work_period = 'both';
            $user->is_active = true;
            $user->must_change_password = false;
            $user->save();
        }

        // Clean any accidental user_permissions on reception accounts
        $receptionUsers = User::where('role', 'reception')->pluck('id');
        UserPermission::whereIn('user_id', $receptionUsers)->delete();

        // Ensure defaults seeder permissions are loaded
        $roleAbilities = [
            'manager' => [
                'members.view', 'members.manage',
                'subscriptions.view', 'subscriptions.manage',
                'attendance.view', 'attendance.record',
                'products.view', 'products.manage',
                'inventory.manage',
                'sales.view', 'sales.create', 'sales.cancel',
                'discounts.formal',
                'appointments.view', 'appointments.manage',
                'nutrition.view',
                'reports.operational',
                'staff.view',
            ],
            'reception' => [
                'members.view', 'members.create', 'members.update',
                'subscriptions.view', 'subscriptions.create',
                'payments.create',
                'attendance.view', 'attendance.record',
                'products.view',
                'sales.create',
                'appointments.view', 'appointments.create', 'appointments.update_unpaid',
                'nutrition_clients.create',
            ],
            'accountant' => [
                'payments.view', 'payments.reverse',
                'refunds.process',
                'expenses.view', 'expenses.manage',
                'purchases.view', 'purchases.manage',
                'sales.view',
                'inventory.view', 'inventory.manage',
                'reports.finance',
                'audit.financial',
            ],
            'nutritionist' => [
                'appointments.own',
                'appointments.complete_own',
                'appointments.cancel_unpaid_own',
                'measurements.own',
            ],
        ];

        DB::table('role_permissions')->truncate();
        $now = now();
        foreach ($roleAbilities as $role => $abilities) {
            foreach ($abilities as $ability) {
                DB::table('role_permissions')->insert([
                    'role' => $role,
                    'ability' => $ability,
                    'allowed' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // Seed default schedules (Sunday to Saturday 08:00 to 22:00) for all nutritionists
        $nutritionists = User::where('role', 'nutritionist')->get();
        foreach ($nutritionists as $nut) {
            for ($day = 0; $day <= 6; $day++) {
                \App\Models\NutritionistSchedule::updateOrCreate([
                    'nutritionist_id' => $nut->id,
                    'day_of_week' => $day,
                ], [
                    'start_time' => '08:00',
                    'end_time' => '22:00',
                    'is_active' => true,
                ]);
            }
        }

        $this->info('==========================================');
        $this->info('      حسابات مستخدمي النظام وصلاحياتها    ');
        $this->info('==========================================');
        foreach (User::orderBy('role')->get() as $u) {
            $canAdmin = $u->role === 'owner' || $u->hasGymPermission('staff.view');
            $this->line("• اسم المستخدم: {$u->username} | الدور: {$u->role} | رابط الإدارة ظاهر؟ " . ($canAdmin ? 'نعم' : 'لا (مخفي)') . " | كلمة المرور: {$defaultPassword}");
        }
        $this->info('==========================================');

        return self::SUCCESS;
    }
}
