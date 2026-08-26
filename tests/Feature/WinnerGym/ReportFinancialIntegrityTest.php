<?php

namespace Tests\Feature\WinnerGym;

use App\Models\Package;
use App\Models\User;
use App\Services\MembershipService;
use App\Services\PaymentService;
use App\Services\ReportService;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportFinancialIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_financial_summary_subtracts_completed_subscription_refunds(): void
    {
        $actor = User::factory()->create(['role' => 'owner']);
        $member = app(MembershipService::class)->create([
            'full_name' => 'عضو تقرير الاسترداد',
            'phone' => '777200001',
            'gender' => 'male',
            'age' => 30,
            'assigned_period' => 'men',
            'registration_date' => now(config('app.timezone'))->toDateString(),
        ], $actor);
        $package = Package::create([
            'name' => 'باقة التقرير',
            'duration_value' => 1,
            'duration_unit' => 'month',
            'price_yer' => 100000,
            'is_active' => true,
            'created_by' => $actor->id,
        ]);

        $subscription = app(SubscriptionService::class)->create([
            'member_id' => $member->id,
            'package_id' => $package->id,
            'period' => 'men',
            'start_date' => now(config('app.timezone'))->toDateString(),
            'currency' => 'YER',
            'discount_amount' => 0,
            'payment_plan' => 'full',
            'installment_count' => 1,
            'first_payment_amount' => 100000,
            'payment_method' => 'cash',
            'installment_due_dates' => [],
        ], $actor);

        app(PaymentService::class)->refund($subscription, [
            'payment_method' => 'cash',
            'reason' => 'اختبار صافي التقرير',
        ], $actor);

        $today = now(config('app.timezone'))->toDateString();
        $summary = app(ReportService::class)->summary($today, $today, 'all', 'YER');

        $this->assertSame(100000.0, $summary['subscription_gross_revenue']);
        $this->assertSame(50000.0, $summary['subscription_refunds']);
        $this->assertSame(50000.0, $summary['subscription_revenue']);
        $this->assertSame(50000.0, $summary['net']);
    }
}
