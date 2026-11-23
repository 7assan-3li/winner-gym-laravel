<?php

namespace Tests\Feature\WinnerGym;

use App\Models\Member;
use App\Models\Package;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberPrintTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthorized_user_cannot_access_member_print_route(): void
    {
        $user = User::factory()->create(['role' => 'reception', 'is_active' => true]);

        $this->actingAs($user)
            ->get(route('members.print'))
            ->assertForbidden();
    }

    public function test_member_print_filters_by_status_period_subscription_status_and_display_options(): void
    {
        $staff = User::factory()->create(['role' => 'reception', 'is_active' => true]);
        UserPermission::create([
            'user_id' => $staff->id,
            'ability' => 'members.view',
            'allowed' => true,
        ]);

        $today = now('Asia/Aden')->toDateString();

        $pkgMonthly = Package::create([
            'name' => 'باقة حديد شهرية',
            'duration_value' => 1,
            'duration_unit' => 'month',
            'price_yer' => 25000,
            'is_active' => true,
            'created_by' => $staff->id,
        ]);

        // 1. Active member with old expired subscription + current active subscription
        $activeMember = Member::create([
            'full_name' => 'سالم علي النشط',
            'phone' => '777111222',
            'gender' => 'male',
            'age' => 26,
            'assigned_period' => 'men',
            'registration_date' => $today,
            'status' => 'active',
            'created_by' => $staff->id,
        ]);
        Subscription::create([
            'member_id' => $activeMember->id,
            'package_id' => $pkgMonthly->id,
            'package_name_snapshot' => $pkgMonthly->name,
            'duration_value_snapshot' => 1,
            'duration_unit_snapshot' => 'month',
            'period' => 'men',
            'start_date' => now('Asia/Aden')->subMonths(2)->toDateString(),
            'end_date' => now('Asia/Aden')->subMonth()->toDateString(),
            'currency' => 'YER',
            'price_snapshot' => 25000,
            'discount_amount' => 0,
            'final_price' => 25000,
            'payment_plan' => 'full',
            'installment_count' => 1,
            'status' => 'expired',
            'created_by' => $staff->id,
        ]);
        Subscription::create([
            'member_id' => $activeMember->id,
            'package_id' => $pkgMonthly->id,
            'package_name_snapshot' => $pkgMonthly->name,
            'duration_value_snapshot' => 1,
            'duration_unit_snapshot' => 'month',
            'period' => 'men',
            'start_date' => $today,
            'end_date' => now('Asia/Aden')->addMonth()->toDateString(),
            'currency' => 'YER',
            'price_snapshot' => 25000,
            'discount_amount' => 0,
            'final_price' => 25000,
            'payment_plan' => 'full',
            'installment_count' => 1,
            'status' => 'active',
            'created_by' => $staff->id,
        ]);

        // 2. Suspended female member with expired subscription only
        $expiredFemale = Member::create([
            'full_name' => 'مريم محمد المنتهي',
            'phone' => '777333444',
            'gender' => 'female',
            'age' => 24,
            'assigned_period' => 'women',
            'registration_date' => $today,
            'status' => 'suspended',
            'created_by' => $staff->id,
        ]);
        Subscription::create([
            'member_id' => $expiredFemale->id,
            'package_id' => $pkgMonthly->id,
            'package_name_snapshot' => $pkgMonthly->name,
            'duration_value_snapshot' => 1,
            'duration_unit_snapshot' => 'month',
            'period' => 'women',
            'start_date' => now('Asia/Aden')->subMonths(2)->toDateString(),
            'end_date' => now('Asia/Aden')->subDays(5)->toDateString(),
            'currency' => 'YER',
            'price_snapshot' => 25000,
            'discount_amount' => 0,
            'final_price' => 25000,
            'payment_plan' => 'full',
            'installment_count' => 1,
            'status' => 'expired',
            'created_by' => $staff->id,
        ]);

        // 3. Member with no subscription at all
        $noSubMember = Member::create([
            'full_name' => 'خالد بدون اشتراك',
            'phone' => '777555666',
            'gender' => 'male',
            'age' => 30,
            'assigned_period' => 'men',
            'registration_date' => $today,
            'status' => 'active',
            'created_by' => $staff->id,
        ]);

        // Test 1: Filter expired subscriptions -> must show ONLY expiredFemale, NOT activeMember
        $this->actingAs($staff)
            ->get(route('members.print', ['subscription_status' => 'expired']))
            ->assertOk()
            ->assertSee('مريم محمد المنتهي')
            ->assertDontSee('سالم علي النشط')
            ->assertDontSee('خالد بدون اشتراك');

        // Test 2: Filter none (no subscription) -> must show ONLY noSubMember
        $this->actingAs($staff)
            ->get(route('members.print', ['subscription_status' => 'none']))
            ->assertOk()
            ->assertSee('خالد بدون اشتراك')
            ->assertDontSee('سالم علي النشط')
            ->assertDontSee('مريم محمد المنتهي');

        // Test 3: Filter period=men & status=active & hide phone
        $this->actingAs($staff)
            ->get(route('members.print', [
                'period' => 'men',
                'status' => 'active',
                'subscription_status' => 'active',
                'package_id' => $pkgMonthly->id,
                'show_phone' => '0',
            ]))
            ->assertOk()
            ->assertSee('سالم علي النشط')
            ->assertDontSee('777111222')
            ->assertDontSee('مريم محمد المنتهي')
            ->assertDontSee('خالد بدون اشتراك');
    }
}
