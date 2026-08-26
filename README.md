# WINNER GYM Management System

نظام تشغيلي متكامل لإدارة النادي: الأعضاء، الاشتراكات والأقساط، الحضور، المبيعات والمخزون، المشتريات والمصروفات، عيادة التغذية، التقارير، الصلاحيات، واتساب والنسخ الاحتياطي.

## متطلبات التشغيل

- PHP 8.3 أو أحدث مع `pdo_pgsql`, `mbstring`, `bcmath`, `zip`.
- PostgreSQL 15 أو أحدث.
- Node.js 22 أو أحدث لبناء الأصول.
- عامل Queue دائم، وCron يشغّل Laravel Scheduler كل دقيقة.

## تجهيز الإنتاج

```bash
cp .env.production.example .env
composer install --no-dev --classmap-authoritative
php artisan key:generate
npm ci
npm run build
php artisan migrate --force
php artisan db:seed --force
php artisan winner-gym:create-owner --generate-password --name="اسم المالك" owner
php artisan optimize
```

عدّل `.env` قبل الترحيل، خصوصًا قاعدة البيانات و`APP_URL` وكلمة تشفير النسخ الاحتياطي. أمر `db:seed` ينشئ الإعدادات والصلاحيات الافتراضية فقط؛ لا ينشئ مستخدمًا تجريبيًا.

شغّل العامل كخدمة دائمة:

```bash
php artisan queue:work --sleep=1 --tries=3 --timeout=120
```

وأضف Cron واحدًا:

```cron
* * * * * cd /path/to/winner-gym && php artisan schedule:run >> /dev/null 2>&1
```

المجدول يحدّث حالات الاشتراكات والأقساط يوميًا، يعالج قواعد واتساب، وينشئ النسخ الاحتياطية حسب الإعدادات.

## بنية الكود

- `app/Livewire`: حالة الواجهة والتحقق الأولي ورفع الملفات فقط.
- `app/Services`: قواعد العمل، الصلاحيات الداخلية، المعاملات والترابط المالي.
- `app/Models`: العلاقات والتحويلات وقوائم الحقول المسموحة.
- `app/Support`: أدوات العرض والتنسيق المشتركة.
- `tests/Feature/WinnerGym`: اختبارات الدورات التشغيلية الكاملة.

أي عملية مالية جديدة يجب أن تمر عبر خدمة، داخل `DB::transaction`، وتستخدم `PaymentPolicy` للتحويلات، وتكتب سجل تدقيق. لا تضع قواعد محاسبية داخل Blade أو JavaScript فقط.

## فحوص التسليم

```bash
composer lint:check
composer types:check
php artisan test
npm run build
php artisan schedule:list
php artisan about --only=environment,cache,drivers
```

قبل التسليم النهائي اختبر نسخة احتياطية وتنزيلها، ثم انسخ الأرشيفات دوريًا إلى تخزين خارجي مشفّر. الاستعادة من داخل النظام غير مفعلة عمدًا؛ تُختبر أولًا على قاعدة منفصلة لمنع الكتابة العرضية فوق الإنتاج.

## ملاحظات أمنية

- لا ترفع `.env` أو مفاتيح واتساب أو كلمات مرور النسخ إلى Git.
- جميع سندات الدفع والإيصالات مخزنة على القرص الخاص وتُعرض عبر مسارات مصادق عليها.
- فعّل HTTPS واضبط `SESSION_SECURE_COOKIE=true` و`APP_DEBUG=false` في الإنتاج.
- احتفظ بنسخة PostgreSQL أصلية دورية خارج الخادم إلى جانب أرشيف النظام.
