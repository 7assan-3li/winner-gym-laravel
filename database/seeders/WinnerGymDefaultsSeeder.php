<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WinnerGymDefaultsSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $settings = [
            ['group' => 'gym', 'key' => 'gym.name', 'value' => json_encode('WINNER GYM', JSON_UNESCAPED_UNICODE)],
            ['group' => 'gym', 'key' => 'gym.location', 'value' => json_encode('المكلا، اليمن', JSON_UNESCAPED_UNICODE)],
            ['group' => 'app', 'key' => 'app.timezone', 'value' => json_encode('Asia/Aden')],
            ['group' => 'finance', 'key' => 'currencies.enabled', 'value' => json_encode(['YER', 'SAR'])],
            ['group' => 'hours', 'key' => 'working_hours.women', 'value' => json_encode(['start' => '08:00', 'end' => '15:00'])],
            ['group' => 'hours', 'key' => 'working_hours.men', 'value' => json_encode(['start' => null, 'end' => null])],
            ['group' => 'whatsapp', 'key' => 'whatsapp.enabled', 'value' => json_encode(false)],
        ];

        foreach ($settings as $setting) {
            DB::table('settings')->updateOrInsert(
                ['key' => $setting['key']],
                [
                    'group' => $setting['group'],
                    'value' => $setting['value'],
                    'updated_at' => $now,
                    'created_at' => $now,
                ],
            );
        }

        $expenseCategories = [
            'الإيجار',
            'الرواتب',
            'الكهرباء والمياه',
            'الصيانة',
            'النظافة',
            'التسويق',
            'أخرى',
        ];

        foreach ($expenseCategories as $name) {
            DB::table('expense_categories')->updateOrInsert(
                ['name' => $name],
                ['is_active' => true, 'updated_at' => $now, 'created_at' => $now],
            );
        }

        $productCategories = [
            'مشروبات',
            'مكملات',
            'إكسسوارات',
            'أخرى',
        ];

        foreach ($productCategories as $name) {
            DB::table('product_categories')->updateOrInsert(
                ['name' => $name],
                ['is_active' => true, 'updated_at' => $now, 'created_at' => $now],
            );
        }

        $measurementTypes = [
            ['code' => 'weight', 'name_ar' => 'الوزن', 'unit' => 'kg', 'sort_order' => 10],
            ['code' => 'height', 'name_ar' => 'الطول', 'unit' => 'cm', 'sort_order' => 20],
            ['code' => 'body_fat', 'name_ar' => 'دهون الجسم', 'unit' => '%', 'sort_order' => 30],
            ['code' => 'muscle', 'name_ar' => 'العضلات', 'unit' => '%', 'sort_order' => 40],
            ['code' => 'water', 'name_ar' => 'الماء', 'unit' => '%', 'sort_order' => 50],
            ['code' => 'visceral_fat', 'name_ar' => 'الدهون الحشوية', 'unit' => null, 'sort_order' => 60],
            ['code' => 'bone_mass', 'name_ar' => 'كتلة العظام', 'unit' => 'kg', 'sort_order' => 70],
            ['code' => 'waist', 'name_ar' => 'محيط الخصر', 'unit' => 'cm', 'sort_order' => 80],
            ['code' => 'chest', 'name_ar' => 'محيط الصدر', 'unit' => 'cm', 'sort_order' => 90],
            ['code' => 'arm', 'name_ar' => 'محيط الذراع', 'unit' => 'cm', 'sort_order' => 100],
            ['code' => 'thigh', 'name_ar' => 'محيط الفخذ', 'unit' => 'cm', 'sort_order' => 110],
        ];

        foreach ($measurementTypes as $type) {
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

        foreach ($roleAbilities as $role => $abilities) {
            foreach ($abilities as $ability) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role' => $role, 'ability' => $ability],
                    ['allowed' => true, 'updated_at' => $now, 'created_at' => $now],
                );
            }
        }

        DB::table('users')
            ->where('role', 'owner')
            ->update(['must_change_password' => false]);
    }
}
