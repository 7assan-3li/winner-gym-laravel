<?php

namespace Tests\Feature\WinnerGym;

use App\Models\Package;
use App\Models\Subscription;
use App\Models\SubscriptionInstallment;
use App\Models\User;
use App\Services\MembershipService;
use App\Services\SubscriptionLifecycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SubscriptionLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_refresh_keeps_subscription_and_installment_statuses_truthful(): void
    {
        Carbon::setTestNow('2026-08-24 12:00:00');

        $actor = User::factory()->create(['role' => 'owner']);
        $package = Package::create([
            'name' => 'باقة دورة الحياة',
            'duration_value' => 1,
            'duration_unit' => 'month',
            'price_yer' => 100000,
            'is_active' => true,
            'created_by' => $actor->id,
        ]);

        $expired = $this->subscription($actor, $package, '777100001', '2026-07-01', '2026-08-23', 'active');
        $activated = $this->subscription($actor, $package, '777100002', '2026-08-24', '2026-09-23', 'upcoming');
        $overdue = $this->subscription($actor, $package, '777100003', '2026-08-01', '2026-09-01', 'active');
        $expiring = $this->subscription($actor, $package, '777100004', '2026-08-01', '2026-08-29', 'active');

        $overdueInstallment = SubscriptionInstallment::create([
            'subscription_id' => $overdue->id,
            'installment_number' => 2,
            'due_date' => '2026-08-20',
            'amount' => 50000,
            'status' => 'pending',
        ]);

        SubscriptionInstallment::create([
            'subscription_id' => $expiring->id,
            'installment_number' => 1,
            'due_date' => '2026-08-01',
            'amount' => 100000,
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $result = app(SubscriptionLifecycleService::class)->refresh();

        $this->assertSame('expired', $expired->fresh()->status);
        $this->assertSame('active', $activated->fresh()->status);
        $this->assertSame('financial_overdue', $overdue->fresh()->status);
        $this->assertSame('overdue', $overdueInstallment->fresh()->status);
        $this->assertSame('expiring_soon', $expiring->fresh()->status);
        $this->assertSame(1, $result['expired']);
        $this->assertSame(1, $result['activated']);
        $this->assertSame(1, $result['overdueInstallments']);
    }

    private function subscription(
        User $actor,
        Package $package,
        string $phone,
        string $start,
        string $end,
        string $status,
    ): Subscription {
        $member = app(MembershipService::class)->create([
            'full_name' => 'عضو '.$phone,
            'phone' => $phone,
            'gender' => 'male',
            'age' => 30,
            'assigned_period' => 'men',
            'registration_date' => '2026-08-01',
        ], $actor);

        return Subscription::create([
            'member_id' => $member->id,
            'package_id' => $package->id,
            'package_name_snapshot' => $package->name,
            'duration_value_snapshot' => 1,
            'duration_unit_snapshot' => 'month',
            'period' => 'men',
            'start_date' => $start,
            'end_date' => $end,
            'currency' => 'YER',
            'price_snapshot' => 100000,
            'discount_amount' => 0,
            'final_price' => 100000,
            'payment_plan' => 'full',
            'installment_count' => 1,
            'status' => $status,
            'created_by' => $actor->id,
        ]);
    }
}
