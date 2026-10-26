<?php

namespace Tests\Feature\WinnerGym;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Package;
use App\Models\Sale;
use App\Models\User;
use App\Services\MembershipService;
use App\Services\ReportService;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashierShiftReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_shift_reconciliation_calculates_cash_and_transfers_and_net_handover(): void
    {
        $cashierOmar = User::factory()->create(['name' => 'عمر المحاسب', 'role' => 'accountant', 'is_active' => true]);
        $cashierAhmed = User::factory()->create(['name' => 'أحمد المحاسب', 'role' => 'accountant', 'is_active' => true]);

        $today = now('Asia/Aden')->toDateString();

        $member = app(MembershipService::class)->create([
            'full_name' => 'عضو الوردية',
            'phone' => '771234567',
            'gender' => 'male',
            'age' => 25,
            'assigned_period' => 'men',
            'registration_date' => $today,
        ], $cashierOmar);

        $package = Package::create([
            'name' => 'باقة شهرية',
            'duration_value' => 1,
            'duration_unit' => 'month',
            'price_yer' => 50000,
            'is_active' => true,
            'created_by' => $cashierOmar->id,
        ]);

        // Omar receives a cash subscription of 50,000 YER
        app(SubscriptionService::class)->create([
            'member_id' => $member->id,
            'package_id' => $package->id,
            'period' => 'men',
            'start_date' => $today,
            'currency' => 'YER',
            'discount_amount' => 0,
            'payment_plan' => 'full',
            'installment_count' => 1,
            'first_payment_amount' => 50000,
            'payment_method' => 'cash',
            'installment_due_dates' => [],
        ], $cashierOmar);

        // Omar records a POS Sale with bank transfer of 15,000 YER
        Sale::create([
            'sale_number' => 'S-001',
            'member_id' => $member->id,
            'customer_name' => 'عضو الوردية',
            'currency' => 'YER',
            'subtotal' => 15000,
            'discount_type' => 'fixed',
            'discount_value' => 0,
            'discount_amount' => 0,
            'total_amount' => 15000,
            'payment_method' => 'transfer',
            'transfer_service' => 'العمقي',
            'transfer_reference' => 'TX-998877',
            'status' => 'completed',
            'sold_at' => now('Asia/Aden'),
            'created_by' => $cashierOmar->id,
        ]);

        // Omar disburses 5,000 YER cash expense from the drawer
        $cat = ExpenseCategory::create(['name' => 'نظافة وضيافة', 'is_active' => true]);
        Expense::create([
            'category_id' => $cat->id,
            'title' => 'شراء مياه للدرج',
            'amount' => 5000,
            'currency' => 'YER',
            'expense_date' => $today,
            'payment_method' => 'cash',
            'status' => 'approved',
            'created_by' => $cashierOmar->id,
        ]);

        // Ahmed receives 30,000 YER in his evening shift
        Sale::create([
            'sale_number' => 'S-002',
            'member_id' => $member->id,
            'customer_name' => 'زبون مسائي',
            'currency' => 'YER',
            'subtotal' => 30000,
            'discount_type' => 'fixed',
            'discount_value' => 0,
            'discount_amount' => 0,
            'total_amount' => 30000,
            'payment_method' => 'cash',
            'status' => 'completed',
            'sold_at' => now('Asia/Aden'),
            'created_by' => $cashierAhmed->id,
        ]);

        $reports = app(ReportService::class);

        // 1. Audit Omar specifically
        $omarSummary = $reports->cashierShiftSummary($cashierOmar->id, $today, 'YER');

        $this->assertSame(50000.0, $omarSummary['subscription_cash']);
        $this->assertSame(15000.0, $omarSummary['sales_transfer']);
        $this->assertSame(5000.0, $omarSummary['cash_expenses']);
        $this->assertSame(50000.0, $omarSummary['total_cash_in']);
        $this->assertSame(15000.0, $omarSummary['total_transfer_in']);
        // Net cash handover for Omar must be: 50,000 cash in - 5,000 cash out = 45,000 YER
        $this->assertSame(45000.0, $omarSummary['net_cash_handover']);
        $this->assertSame(3, $omarSummary['transactions_count']);

        // 2. Audit Ahmed specifically
        $ahmedSummary = $reports->cashierShiftSummary($cashierAhmed->id, $today, 'YER');
        $this->assertSame(30000.0, $ahmedSummary['sales_cash']);
        $this->assertSame(30000.0, $ahmedSummary['net_cash_handover']);
        $this->assertSame(1, $ahmedSummary['transactions_count']);

        // 3. Combined Audit (All cashiers)
        $allSummary = $reports->cashierShiftSummary(null, $today, 'YER');
        $this->assertSame(80000.0, $allSummary['total_cash_in']); // 50,000 + 30,000
        $this->assertSame(15000.0, $allSummary['total_transfer_in']);
        $this->assertSame(75000.0, $allSummary['net_cash_handover']); // 80,000 - 5,000
    }
}
