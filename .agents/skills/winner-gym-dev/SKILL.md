---
name: winner-gym-dev
description: >-
  دليل ومعايير التطوير الإلزامية لنظام WINNER GYM. استخدم هذه المهارة عند كتابة أو تعديل أي ميزة، خدمة، مسار، أو واجهة داخل المشروع لضمان التوافق مع المعمارية والأمان المالي.
---

# دليل التطوير ومعايير نظام WINNER GYM

هذا الدليل يحدد الخطوات التفصيلية، النماذج البرمجية، والمحددات الصارمة لتطوير وصيانة نظام إدارة الصالة الرياضية **WINNER GYM**.

---

## 1. هيكل المجلدات والمسؤوليات (Architectural Blueprint)

```
app/
├── Models/              # كائنات Eloquent، العلاقات، الـ Casts، والـ Scopes
├── Services/            # المنطق البرمجي، المعاملات المالية، وسجلات التدقيق
├── Livewire/            # الواجهات التفاعلية، المدخلات، الرفع، والتحقق السطحي
├── Http/
│   ├── Controllers/     # تصدير ملفات PDF، التنزيلات الخاصة، والاستعلامات العامة
│   └── Middleware/      # فحص الصلاحيات (gym.any, gym.owner)
└── Support/             # أدوات المساعدة، التنسيق المالي والتواريخ
```

---

## 2. نمط كتابة العمليات المالية (Financial Operation Pattern)

كل عملية مالية (سداد، استرداد، مبيعات، مشتريات، اشتراكات، مصروفات) **يجب** أن تتبع النمط التالي بدقة:

```php
namespace App\Services;

use App\Models\User;
use App\Services\AuditService;
use App\Services\PaymentPolicy;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomOperationService
{
    public function __construct(
        private AuditService $audit,
        private PaymentPolicy $paymentPolicy,
    ) {}

    /** @param array<string, mixed> $data */
    public function execute(array $data, User $actor): SomeModel
    {
        // 1. التحقق من سياسة الدفع إذا تضمنت العملية تحويلاً
        $this->paymentPolicy->validate($data);

        return DB::transaction(function () use ($data, $actor) {
            // 2. استخدام القفل التشاؤمي على السجلات المرتبطة
            $record = SomeModel::query()->lockForUpdate()->findOrFail($data['id']);

            // 3. التحقق من صحة المبالغ والعملة
            $amount = round((float) $data['amount'], 2);
            if ($amount <= 0) {
                throw ValidationException::withMessages(['amount' => 'المبلغ يجب أن يكون أكبر من صفر.']);
            }

            // 4. تنفيذ العملية وتحديث السجلات
            $created = TransactionModel::create([
                'amount' => $amount,
                'currency' => $data['currency'], // YER أو SAR
                'payment_method' => $data['payment_method'],
                'transfer_service' => $data['payment_method'] === 'transfer' ? $data['transfer_service'] : null,
                'transfer_reference' => $data['payment_method'] === 'transfer' ? $data['transfer_reference'] : null,
                'proof_path' => $data['payment_method'] === 'transfer' ? ($data['proof_path'] ?? null) : null,
                'created_by' => $actor->id,
            ]);

            // 5. تسجيل سجل التدقيق الإلزامي
            $this->audit->log($actor, 'finance', 'custom_operation.created', $created, null, $created->toArray());

            return $created;
        });
    }
}
```

---

## 3. قواعد العمل الخاصة بالوحدات (Business Logic Rules)

### أ) الاشتراكات والأقساط (`SubscriptionService`)
1. **التحقق من حالة العضو والفترة**: لا يمكن إنشاء اشتراك لعضو غير نشط (`status !== 'active'`)، ويجب مطابقة فترة الاشتراك (`men`/`women`) مع فترة العضو.
2. **العملات**: استخدام العملة المحددة في الباقة (`price_yer` أو `price_sar`) فقط.
3. **منع التداخل**: إذا كان للعضو اشتراك سارٍ، يبدأ الاشتراك الجديد في اليوم التالي لنهاية الاشتراك الساري.
4. **خطة التقسيط**:
   - الدفعة الأولى لا تقل عن **50%** من السعر النهائي بعد الخصم.
   - جدولة باقي الأقساط بتواريخ محددة وحفظ مبالغها في `subscription_installments`.
5. **الاسترداد (Refund)**:
   - الحد الأقصى للمبلغ المسترد هو 50% من قيمة الاشتراك الإجمالية أو إجمالي المدفوع (أيهما أقل).
   - تحويل حالة الاشتراك إلى `refunded`.

### ب) المخزون والمبيعات (`InventoryService`)
1. **اعتماد المشتريات (Purchases)**:
   - تحديث الكمية: `current_quantity = current_quantity + purchase_quantity`.
   - تحديث التكلفة بالمتوسط المرجح:
     $$\text{Average Cost} = \frac{(\text{Old Qty} \times \text{Old Cost}) + (\text{New Qty} \times \text{New Cost})}{\text{Old Qty} + \text{New Qty}}$$
   - تسجيل حركة في `inventory_movements` بنوع `purchase`.
2. **المبيعات (Sales)**:
   - فحص توفر الكمية تحت القفل `lockForUpdate()`.
   - خصم الكمية من المنتج وتسجيل حركة `inventory_movements` بنوع `sale`.
   - لا يمكن تعديل سعر البيع إلا لمن يملك صلاحية `discounts.formal`.
3. **إلغاء البيع (Sale Cancellation)**:
   - إعادة الكميات المباعة للمخزن.
   - تسجيل حركة مخزنية بنوع `sale_cancel`.
   - تحديث حالة الفاتورة إلى `cancelled` مع توثيق سبب الإلغاء ومن قام به.

### ج) تسجيل الحضور (`AttendanceService`)
1. **التحقق من الشروط التالية قبل السماح بالدخول**:
   - العضو نشط (`status === 'active'`).
   - الوقت الحالي يقع ضمن ساعات العمل المحددة للفترة (رجال/نساء).
   - اشتراك العضو سارٍ لليوم الحالي.
   - لا توجد أقساط متأخرة (`status !== 'financial_overdue'`).
2. **تسجيل المحاولات**: كل محاولة دخول (سواء نجحت أو رُفضت مع بيان سبب الرفض) تسجل في `attendance_attempts`.

### د) عيادة التغذية (`AppointmentService` & `MeasurementService`)
1. **العملاء**: يدعم النظام حجز المواعيد للأعضاء المسجلين (`member_id`) أو للعملاء الخارجيين (`nutrition_client_id`).
2. **منع تضارب المواعيد**: التأكد من عدم حجز موعد لنفس الأخصائي في نفس التاريخ والوقت.
3. **القياسات الجسدية**: تسجيل قياسات الجسم الشاملة وحساب مؤشر كتلة الجسم (BMI) تلقائياً:
   $$\text{BMI} = \frac{\text{Weight (kg)}}{(\text{Height (m)})^2}$$

---

## 4. الصلاحيات والأمان (Permissions & Authorization)

1. **التحقق في المكونات (Livewire Components)**:
   ```php
   abort_unless(app(PermissionService::class)->allows(auth()->user(), 'ability.name'), 403);
   ```
2. **التحقق في المسارات (Route Middleware)**:
   ```php
   // صلاحية محددة أو مالك
   Route::get('/example', ExampleComponent::class)->middleware('gym.any:members.view,members.manage');
   
   // مالك النظام فقط
   Route::get('/packages', PackagesComponent::class)->middleware('gym.owner');
   ```
3. **حماية المرفقات والملفات الخاصة**:
   - تخزن المستندات، سندات الإيداع، وإيصالات الدفع في القرص الخاص `Storage::disk('local')`.
   - تُعرض فقط عبر مسارات محمية ومصادق عليها مع فحص الصلاحيات المناسبة.

---

## 5. واجهات المستخدم والتنسيق (Livewire & UI Rules)

1. **إعادة ضبط التصفح (Pagination Reset)**:
   - عند تحديث أي فلتر أو حقل بحث، استدعاء `$this->resetPage()` لمنع عرض صفحات فارغة.
2. **التصميم المتناسق**:
   - استخدام قوالب Blade المتوافقة مع RTL.
   - الاعتماد على كلاسات ونظام الألوان الموحد في `public/winner-gym/*.css`.
   - دعم الوضعين الليلي والنهاري (Dark/Light Mode).

---

## 6. قائمة التحقق قبل اعتماد أي كود (Verification Checklist)

قبل إنهاء أي تعديل، تأكد من استيفاء ما يلي:

- [ ] تم تغليف كافة التعديلات المالية والمخزنية داخل `DB::transaction()`.
- [ ] تم استخدام `lockForUpdate()` على السجلات المالية الحساسة.
- [ ] تم تسجيل الحركة في `audit_logs`.
- [ ] لم يتم خلط عملتي `YER` و `SAR`.
- [ ] تم تطبيق فحص الصلاحيات المناسب (`PermissionService`).
- [ ] تم تشغيل أدوات فحص التنسيق والجودة:
  ```bash
  composer lint:check
  composer types:check
  php artisan test
  ```
