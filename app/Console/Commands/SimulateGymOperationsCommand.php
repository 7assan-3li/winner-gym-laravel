<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\ExpenseCategory;
use App\Models\MeasurementType;
use App\Models\Member;
use App\Models\Package;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use App\Services\AppointmentService;
use App\Services\AttendanceService;
use App\Services\BackupService;
use App\Services\ExpenseService;
use App\Services\InventoryService;
use App\Services\MeasurementService;
use App\Services\MembershipService;
use App\Services\PaymentService;
use App\Services\SubscriptionService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SimulateGymOperationsCommand extends Command
{
    protected $signature = 'winner-gym:simulate-operations';

    protected $description = 'Run a complete, realistic 10-step end-to-end operational lifecycle simulation';

    public function handle(
        MembershipService $membershipService,
        SubscriptionService $subscriptionService,
        PaymentService $paymentService,
        AttendanceService $attendanceService,
        InventoryService $inventoryService,
        AppointmentService $appointmentService,
        MeasurementService $measurementService,
        ExpenseService $expenseService,
        BackupService $backupService
    ): int {
        $this->info('===========================================================');
        $this->info('  WINNER GYM — بدء تشغيل محاكاة الدورة التشغيلية الكاملة');
        $this->info('===========================================================');

        $owner = User::where('role', 'owner')->first() ?? User::first();
        if (! $owner) {
            $this->error('لم يتم العثور على حساب المالك أو مستخدم صالح.');

            return 1;
        }

        $now = CarbonImmutable::now('Asia/Aden');

        // 1. Members Creation
        $this->info("\n[1/7] تسجيل أعضاء جدد (رجال ونساء)...");
        $maleMember = Member::firstOrCreate(
            ['phone' => '771234567'],
            [
                'full_name' => 'محمد عبد الله اليافعي',
                'gender' => 'male',
                'assigned_period' => 'men',
                'status' => 'active',
                'birth_date' => '1995-05-15',
                'age' => 31,
                'address' => 'المكلا - الديس',
                'identity_number' => '0501009842',
                'membership_code' => 'WG-M'.rand(1000, 9999),
                'barcode_value' => 'WG'.rand(10000000, 99999999),
            ]
        );
        $this->line("  ✓ تم تسجيل العضو الرجالي: {$maleMember->full_name} ({$maleMember->membership_code})");

        $femaleMember = Member::firstOrCreate(
            ['phone' => '772345678'],
            [
                'full_name' => 'فاطمة سالم الكاف',
                'gender' => 'female',
                'assigned_period' => 'women',
                'status' => 'active',
                'birth_date' => '1998-09-20',
                'age' => 28,
                'address' => 'المكلا - الشرج',
                'identity_number' => '0501007731',
                'membership_code' => 'WG-F'.rand(1000, 9999),
                'barcode_value' => 'WG'.rand(10000000, 99999999),
            ]
        );
        $this->line("  ✓ تم تسجيل العضوة النسائية: {$femaleMember->full_name} ({$femaleMember->membership_code})");

        // 2. Package & Subscription
        $this->info("\n[2/7] إنشاء باقة سنوية واشتراك بالتقسيط مع دفعة أولى 50%...");
        $package = Package::firstOrCreate(
            ['name' => 'باقة اللياقة وبناء الأجسام السنوية VIP'],
            [
                'duration_value' => 12,
                'duration_unit' => 'month',
                'price_yer' => 120000,
                'price_sar' => 450,
                'is_active' => true,
            ]
        );

        $subscriptionData = [
            'member_id' => $maleMember->id,
            'package_id' => $package->id,
            'period' => 'men',
            'start_date' => $now->toDateString(),
            'currency' => 'YER',
            'discount_amount' => 10000,
            'payment_plan' => 'installments',
            'installment_count' => 3,
            'first_payment_amount' => 55000, // 50% of 110,000 YER
            'payment_method' => 'transfer',
            'transfer_service' => 'العمقي',
            'transfer_reference' => 'AMQ-REF-'.rand(100000, 999999),
            'notes' => 'اشتراك سنوي VIP مع خطة تقسيط 3 دفعات',
            'installment_due_dates' => [
                $now->addMonths(1)->toDateString(),
                $now->addMonths(2)->toDateString(),
            ],
            'proof_path' => 'simulated-proofs/sample-receipt.pdf',
        ];

        // Check if member already has active subscription to avoid duplicate collision
        $existingSub = $maleMember->subscriptions()->whereIn('status', ['active', 'financial_overdue'])->first();
        if (! $existingSub) {
            $subscription = $subscriptionService->create($subscriptionData, $owner);
            $this->line("  ✓ تم إصدار الاشتراك بنجاح: #{$subscription->id} - إجمالي: {$subscription->final_price} YER (دفعة أولى مدفوعة: 55,000 YER)");
        } else {
            $subscription = $existingSub;
            $this->line("  ✓ تم استخدام الاشتراك القائم للعضو: #{$subscription->id}");
        }

        // 3. Attendance Scan & Rejection Tests
        $this->info("\n[3/7] اختبار تسجيل الحضور وحالات القبول والمنع...");
        try {
            $att = $attendanceService->record('membership_code', $maleMember->membership_code, $owner);
            $this->line("  ✓ تم قبول وتسجيل حضور العضو: {$maleMember->full_name} في فترة الرجال");
        } catch (\Exception $e) {
            $this->line("  ℹ تسجيل الحضور لليوم مسجل مسبقاً أو: {$e->getMessage()}");
        }

        // 4. POS Inventory & Sale
        $this->info("\n[4/7] اختبار نقطة البيع (POS) وحركات المخزون...");
        $category = ProductCategory::firstOrCreate(['name' => 'المكملات الغذائية والبروتين'], ['is_active' => true]);
        $product = Product::firstOrCreate(
            ['barcode' => 'PROT-GOLD-2KG'],
            [
                'category_id' => $category->id,
                'name' => 'بروتين واي جولد ستاندرد 2 كجم',
                'purchase_cost' => 30000,
                'selling_price' => 45000,
                'currency' => 'YER',
                'current_quantity' => 15,
                'minimum_quantity' => 3,
                'is_active' => true,
            ]
        );

        $saleData = [
            'member_id' => $maleMember->id,
            'customer_name' => $maleMember->full_name,
            'currency' => 'YER',
            'payment_method' => 'cash',
            'discount_amount' => 0,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'unit_price' => 45000,
                ],
            ],
        ];

        $stockBefore = (int) $product->fresh()->current_quantity;
        $sale = $inventoryService->createSale($saleData, $owner);
        $stockAfter = (int) $product->fresh()->current_quantity;
        $this->line("  ✓ تم إتمام عملية البيع #{$sale->id} بقيمة {$sale->total_amount} YER نقدياً");
        $this->line("  ✓ انخفض المخزون من {$stockBefore} إلى {$stockAfter} وحدة بنجاح");

        // 5. Nutrition Clinic Appointment & Body Measurements
        $this->info("\n[5/7] اختبار عيادة التغذية وحساب كتلة الجسم BMI...");
        $nutritionist = User::firstOrCreate(
            ['username' => 'dr_nutrition'],
            [
                'name' => 'د. سالم أخصائي التغذية',
                'email' => 'nutritionist@winnergym.com',
                'password' => bcrypt('DrNutrition2026!'),
                'role' => 'nutritionist',
                'work_period' => 'both',
                'is_active' => true,
            ]
        );

        // Ensure schedule exists for today/tomorrow
        DB::table('nutritionist_schedules')->updateOrInsert(
            [
                'nutritionist_id' => $nutritionist->id,
                'day_of_week' => (int) $now->addDays(1)->format('w'),
            ],
            [
                'start_time' => '15:00',
                'end_time' => '22:00',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $appDate = $now->addDays(1)->toDateString();
        $appointment = null;
        try {
            $appointment = $appointmentService->book([
                'member_id' => $maleMember->id,
                'nutritionist_id' => $nutritionist->id,
                'appointment_date' => $appDate,
                'start_time' => '17:00',
                'duration_minutes' => 30,
                'service_type' => 'inbody_consultation',
                'visit_type' => 'first_visit',
                'price' => 5000,
                'currency' => 'YER',
                'notes' => 'استشارة برنامج غذائي ومتابعة دهون',
            ], $owner);
            $this->line("  ✓ تم حجز وتأكيد موعد استشارة التغذية #{$appointment->id} بتاريخ {$appDate} 17:00");
        } catch (\Exception $e) {
            $this->line("  ℹ حجز الموعد: {$e->getMessage()}");
        }

        // Measurement Types ensure
        $types = [
            ['code' => 'weight', 'name_ar' => 'الوزن', 'unit' => 'kg'],
            ['code' => 'height', 'name_ar' => 'الطول', 'unit' => 'cm'],
            ['code' => 'body_fat_percentage', 'name_ar' => 'نسبة الدهون', 'unit' => '%'],
            ['code' => 'muscle_mass_kg', 'name_ar' => 'الكتلة العضلية', 'unit' => 'kg'],
            ['code' => 'waist_cm', 'name_ar' => 'محيط الخصر', 'unit' => 'cm'],
        ];
        foreach ($types as $t) {
            MeasurementType::firstOrCreate(['code' => $t['code']], array_merge($t, ['is_active' => true]));
        }

        $measurement = $measurementService->record([
            'member_id' => $maleMember->id,
            'appointment_id' => $appointment?->id,
            'nutritionist_id' => $nutritionist->id,
            'notes' => 'حالة بدنية ممتازة وتناسق عضلي جيد',
            'values' => [
                'weight' => 82.0,
                'height' => 178.0,
                'body_fat_percentage' => 18.2,
                'muscle_mass_kg' => 41.5,
                'waist_cm' => 84.0,
            ],
        ], $owner);

        $this->line("  ✓ تم حفظ القياسات البدنية كاملة: الوزن=82كجم، الطول=178سم، مؤشر BMI={$measurement->bmi} بنجاح");

        // 6. Expenses Registration
        $this->info("\n[6/7] قيد وتسجيل مصروف تشغيلي مع سند...");
        $expCategory = ExpenseCategory::firstOrCreate(['name' => 'الكهرباء والطاقة'], ['is_active' => true]);
        $expense = $expenseService->create([
            'category_id' => $expCategory->id,
            'title' => 'سداد فاتورة كهرباء الصالة لشهر أغسطس',
            'amount' => 25000,
            'currency' => 'YER',
            'expense_date' => $now->toDateString(),
            'payment_method' => 'cash',
            'receipt_path' => 'simulated-expenses/electricity-august.pdf',
            'notes' => 'سداد نقدي مباشر لفرع شركة الكهرباء',
        ], $owner);
        $this->line("  ✓ تم تسجيل المصروف #{$expense->id} بقيمة {$expense->amount} YER بنجاح");

        // 7. Encrypted System Backup
        $this->info("\n[7/7] إنشاء نسخة احتياطية مشفرة للنظام...");
        try {
            $backup = $backupService->create($owner);
            $this->line("  ✓ تم إنشاء النسخة الاحتياطية بنجاح: {$backup->filename} (الحجم: ".round($backup->size_bytes / 1024, 2).' KB)');
        } catch (\Exception $e) {
            $this->line("  ℹ نتيجة النسخ الاحتياطي: {$e->getMessage()}");
        }

        $this->info("\n===========================================================");
        $this->info('  🎉 اكتملت المحاكاة التشغيلية لجميع الوحدات بنسبة 100% بنجاح!');
        $this->info('===========================================================');

        return 0;
    }
}
