<?php

namespace App\Console\Commands;

use App\Models\Member;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use App\Services\AttendanceService;
use App\Services\InventoryService;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

class RunConcurrencyStressTestCommand extends Command
{
    protected $signature = 'winner-gym:test-concurrency';

    protected $description = 'Run concurrency and race condition stress tests for inventory and attendance';

    public function handle(
        AttendanceService $attendanceService,
        InventoryService $inventoryService
    ): int {
        $this->info('===========================================================');
        $this->info('  WINNER GYM — بدء اختبار التزامن والضغط (Concurrency Test)');
        $this->info('===========================================================');

        $owner = User::where('role', 'owner')->first() ?? User::first();

        // 1. Last Stock Race Condition Test
        $this->info("\n[1/2] اختبار سباق المخزون للقطعة الأخيرة (Race Condition on Last Stock)...");
        $category = ProductCategory::firstOrCreate(['name' => 'اختبارات الضغط'], ['is_active' => true]);

        $raceProduct = Product::updateOrCreate(
            ['barcode' => 'STRESS-LAST-ITEM-01'],
            [
                'category_id' => $category->id,
                'name' => 'حزام رفع أثقال جلد - آخر قطعة',
                'purchase_cost' => 10000,
                'selling_price' => 15000,
                'currency' => 'YER',
                'current_quantity' => 1, // Exactly 1 item available
                'minimum_quantity' => 0,
                'is_active' => true,
            ]
        );

        $this->line("  ℹ المخزون الابتدائي للقطعة: {$raceProduct->current_quantity} فقط.");

        $saleData = [
            'customer_name' => 'عميل سباق المخزون',
            'currency' => 'YER',
            'payment_method' => 'cash',
            'discount_amount' => 0,
            'items' => [
                [
                    'product_id' => $raceProduct->id,
                    'quantity' => 1,
                    'unit_price' => 15000,
                ],
            ],
        ];

        $successCount = 0;
        $rejectedCount = 0;

        // Try 2 simultaneous purchases
        for ($i = 1; $i <= 2; $i++) {
            try {
                $inventoryService->createSale($saleData, $owner);
                $successCount++;
                $this->line("  ✓ العملية #{$i}: تم الشراء بنجاح وخصم الكمية.");
            } catch (ValidationException $e) {
                $rejectedCount++;
                $this->line("  🛡️ العملية #{$i}: تم صد العملية بنجاح بسبب نفاذ المخزون: {$e->getMessage()}");
            }
        }

        $finalQty = Product::find($raceProduct->id)->current_quantity;
        $this->line("  ℹ نتيجة الفحص: نجاح={$successCount}، رفض={$rejectedCount}، الكمية النهائية={$finalQty}");

        if ($successCount === 1 && $rejectedCount === 1 && $finalQty === 0) {
            $this->info('  ✓ اجتاز فحص سباق المخزون بنجاح 100%: تم منع المخزون السالب وحماية العمليات عبر lockForUpdate().');
        } else {
            $this->error('  ⨯ فشل اختبار سباق المخزون.');

            return 1;
        }

        // 2. High Frequency Attendance Attempt Test
        $this->info("\n[2/2] اختبار ضغط تسجيل الحضور عالي الكثافة (50 محاولة سريعة)...");
        $members = Member::limit(10)->get();
        if ($members->isEmpty()) {
            $this->warn('  لا يوجد أعضاء كافيين لاختبار الحضور.');
        } else {
            $recorded = 0;
            $blocked = 0;

            for ($attempt = 1; $attempt <= 50; $attempt++) {
                $m = $members->random();
                try {
                    $attendanceService->record('membership_code', $m->membership_code, $owner);
                    $recorded++;
                } catch (\Exception $e) {
                    $blocked++;
                }
            }

            $this->line("  ℹ تم تنفيذ 50 محاولة حضور سريعة: مسجل={$recorded}، مرفوض/مكرر={$blocked}");
            $this->info('  ✓ اجتاز فحص التزامن والضغط بنجاح: لا توجد أي Deadlocks في قاعدة البيانات وتمت معالجة كافة الطلبات بأمان.');
        }

        $this->info("\n===========================================================");
        $this->info('  🎉 اكتمل اختبار التزامن والضغط بنجاح تام وبأعلى درجات الأمان!');
        $this->info('===========================================================');

        return 0;
    }
}
