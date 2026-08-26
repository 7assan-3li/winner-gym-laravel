<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('must_change_password')->default(false);
            $table->timestampTz('last_login_at')->nullable();
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('role', 40);
            $table->string('ability', 120);
            $table->boolean('allowed')->default(true);
            $table->timestampsTz();
            $table->unique(['role', 'ability']);
        });

        Schema::create('user_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('ability', 120);
            $table->boolean('allowed')->default(true);
            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
            $table->unique(['user_id', 'ability']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('category', 40);
            $table->string('action', 120);
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->jsonb('old_values')->nullable();
            $table->jsonb('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->index(['category', 'action']);
            $table->index(['auditable_type', 'auditable_id']);
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('group', 60)->default('general');
            $table->string('key', 150)->unique();
            $table->jsonb('value')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
        });

        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('membership_code', 32)->unique();
            $table->string('full_name');
            $table->string('phone', 30)->unique();
            $table->string('gender', 10);
            $table->date('birth_date')->nullable();
            $table->unsignedSmallInteger('age')->nullable();
            $table->string('assigned_period', 10);
            $table->string('barcode_value', 100)->unique();
            $table->string('qr_value', 255)->unique();
            $table->date('registration_date');
            $table->string('status', 20)->default('active');
            $table->string('address')->nullable();
            $table->string('identity_number', 100)->nullable();
            $table->string('identity_image_path')->nullable();
            $table->string('profile_image_path')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('archived_at')->nullable();
            $table->foreignId('archived_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
            $table->index(['gender', 'status']);
            $table->index('assigned_period');
        });

        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('duration_value');
            $table->string('duration_unit', 10);
            $table->decimal('price_yer', 18, 2)->nullable();
            $table->decimal('price_sar', 18, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->restrictOnDelete();
            $table->foreignId('package_id')->constrained('packages')->restrictOnDelete();
            $table->string('package_name_snapshot');
            $table->unsignedInteger('duration_value_snapshot');
            $table->string('duration_unit_snapshot', 10);
            $table->string('period', 10);
            $table->date('start_date');
            $table->date('end_date');
            $table->string('currency', 3);
            $table->decimal('price_snapshot', 18, 2);
            $table->decimal('discount_amount', 18, 2)->default(0);
            $table->decimal('final_price', 18, 2);
            $table->string('payment_plan', 20);
            $table->unsignedSmallInteger('installment_count')->default(1);
            $table->string('status', 30)->default('upcoming');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('cancellation_reason')->nullable();
            $table->timestampsTz();
            $table->index(['member_id', 'status']);
            $table->index(['end_date', 'status']);
            $table->index(['currency', 'status']);
        });

        Schema::create('subscription_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('subscriptions')->restrictOnDelete();
            $table->unsignedSmallInteger('installment_number');
            $table->date('due_date');
            $table->decimal('amount', 18, 2);
            $table->string('status', 20)->default('pending');
            $table->timestampTz('paid_at')->nullable();
            $table->timestampsTz();
            $table->unique(['subscription_id', 'installment_number']);
            $table->index(['due_date', 'status']);
        });

        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('subscriptions')->restrictOnDelete();
            $table->foreignId('installment_id')->nullable()->constrained('subscription_installments')->restrictOnDelete();
            $table->decimal('amount', 18, 2);
            $table->string('currency', 3);
            $table->string('payment_method', 20);
            $table->string('transfer_service')->nullable();
            $table->string('transfer_reference')->nullable();
            $table->string('proof_path')->nullable();
            $table->string('receipt_number', 50)->unique();
            $table->string('status', 20)->default('completed');
            $table->timestampTz('paid_at');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('reversed_at')->nullable();
            $table->text('reversal_reason')->nullable();
            $table->timestampsTz();
            $table->index(['subscription_id', 'status']);
            $table->index(['currency', 'paid_at']);
        });

        Schema::create('subscription_refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->unique()->constrained('subscriptions')->restrictOnDelete();
            $table->decimal('amount', 18, 2);
            $table->string('currency', 3);
            $table->string('payment_method', 20);
            $table->string('transfer_service')->nullable();
            $table->string('transfer_reference')->nullable();
            $table->string('proof_path')->nullable();
            $table->text('reason')->nullable();
            $table->string('status', 20)->default('completed');
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('processed_at');
            $table->timestampsTz();
        });

        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->restrictOnDelete();
            $table->foreignId('subscription_id')->constrained('subscriptions')->restrictOnDelete();
            $table->date('attendance_date');
            $table->timestampTz('entered_at');
            $table->string('method', 20);
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
            $table->unique(['member_id', 'attendance_date']);
            $table->index(['attendance_date', 'entered_at']);
        });

        Schema::create('attendance_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->string('identifier')->nullable();
            $table->string('method', 20);
            $table->boolean('allowed')->default(false);
            $table->string('rejection_reason')->nullable();
            $table->timestampTz('attempted_at');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->index(['attempted_at', 'allowed']);
        });

        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('expense_categories')->restrictOnDelete();
            $table->string('title');
            $table->decimal('amount', 18, 2);
            $table->string('currency', 3);
            $table->date('expense_date');
            $table->string('payment_method', 20);
            $table->string('transfer_reference')->nullable();
            $table->string('receipt_path')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('approved');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('approved_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestampsTz();
            $table->index(['currency', 'expense_date', 'status']);
        });

        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('product_categories')->restrictOnDelete();
            $table->string('name');
            $table->string('image_path')->nullable();
            $table->string('barcode', 100)->nullable()->unique();
            $table->decimal('purchase_cost', 18, 2)->default(0);
            $table->decimal('selling_price', 18, 2)->default(0);
            $table->string('currency', 3);
            $table->integer('current_quantity')->default(0);
            $table->integer('minimum_quantity')->default(0);
            $table->string('status', 20)->default('active');
            $table->text('notes')->nullable();
            $table->timestampsTz();
            $table->index(['status', 'current_quantity']);
        });

        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->string('purchase_number', 50)->unique();
            $table->date('purchase_date');
            $table->string('currency', 3);
            $table->string('status', 20)->default('pending');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('approved_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestampsTz();
            $table->index(['purchase_date', 'status']);
        });

        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('purchases')->restrictOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_cost', 18, 2);
            $table->decimal('line_total', 18, 2);
            $table->unique(['purchase_id', 'product_id']);
        });

        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('sale_number', 50)->unique();
            $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->string('customer_name')->nullable();
            $table->string('currency', 3);
            $table->decimal('subtotal', 18, 2);
            $table->string('discount_type', 10)->nullable();
            $table->decimal('discount_value', 18, 2)->default(0);
            $table->decimal('discount_amount', 18, 2)->default(0);
            $table->decimal('total_amount', 18, 2);
            $table->string('payment_method', 20);
            $table->string('transfer_reference')->nullable();
            $table->string('proof_path')->nullable();
            $table->string('status', 20)->default('completed');
            $table->timestampTz('sold_at');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestampsTz();
            $table->index(['currency', 'sold_at', 'status']);
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->restrictOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('original_unit_price', 18, 2);
            $table->decimal('actual_unit_price', 18, 2);
            $table->decimal('unit_cost', 18, 2);
            $table->decimal('line_total', 18, 2);
            $table->boolean('price_overridden')->default(false);
            $table->foreignId('price_overridden_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('price_overridden_at')->nullable();
        });

        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->string('movement_type', 30);
            $table->integer('quantity_delta');
            $table->integer('quantity_before');
            $table->integer('quantity_after');
            $table->decimal('unit_cost', 18, 2)->nullable();
            $table->string('reference_type', 40)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->index(['product_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
        });

        Schema::create('nutrition_clients', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('phone', 30);
            $table->string('gender', 10)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
            $table->index('phone');
        });

        Schema::create('nutritionist_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nutritionist_id')->constrained('users')->restrictOnDelete();
            $table->unsignedTinyInteger('day_of_week');
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
            $table->unique(['nutritionist_id', 'day_of_week', 'start_time', 'end_time']);
        });

        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nutritionist_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->foreignId('nutrition_client_id')->nullable()->constrained('nutrition_clients')->nullOnDelete();
            $table->date('appointment_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedSmallInteger('duration_minutes');
            $table->decimal('price', 18, 2);
            $table->string('currency', 3);
            $table->string('status', 20)->default('booked');
            $table->string('payment_status', 20)->default('unpaid');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('completed_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestampsTz();
            $table->unique(['nutritionist_id', 'appointment_date', 'start_time']);
            $table->index(['appointment_date', 'status']);
        });

        Schema::create('appointment_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->unique()->constrained('appointments')->restrictOnDelete();
            $table->decimal('amount', 18, 2);
            $table->string('currency', 3);
            $table->string('payment_method', 20);
            $table->string('transfer_reference')->nullable();
            $table->string('proof_path')->nullable();
            $table->string('status', 20)->default('paid');
            $table->timestampTz('paid_at');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('reversed_at')->nullable();
            $table->text('reversal_reason')->nullable();
            $table->timestampsTz();
            $table->index(['currency', 'paid_at']);
        });

        Schema::create('measurement_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 60)->unique();
            $table->string('name_ar');
            $table->string('unit', 30)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestampsTz();
        });

        Schema::create('measurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->foreignId('nutrition_client_id')->nullable()->constrained('nutrition_clients')->nullOnDelete();
            $table->foreignId('nutritionist_id')->constrained('users')->restrictOnDelete();
            $table->decimal('bmi', 8, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestampTz('measured_at');
            $table->timestampsTz();
            $table->index(['member_id', 'measured_at']);
            $table->index(['nutrition_client_id', 'measured_at']);
        });

        Schema::create('measurement_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('measurement_id')->constrained('measurements')->cascadeOnDelete();
            $table->foreignId('measurement_type_id')->constrained('measurement_types')->restrictOnDelete();
            $table->decimal('value', 14, 3);
            $table->unique(['measurement_id', 'measurement_type_id']);
        });

        Schema::create('whatsapp_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type', 30);
            $table->integer('days_offset')->nullable();
            $table->text('message_template')->nullable();
            $table->boolean('is_enabled')->default(false);
            $table->string('mode', 10)->default('manual');
            $table->string('audience', 10)->default('all');
            $table->unsignedInteger('duplicate_window_days')->default(30);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
            $table->index(['type', 'is_enabled']);
        });

        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rule_id')->nullable()->constrained('whatsapp_rules')->nullOnDelete();
            $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->string('phone', 30);
            $table->text('message');
            $table->string('status', 20);
            $table->string('mode', 10);
            $table->string('provider_message_id')->nullable();
            $table->text('error_message')->nullable();
            $table->string('dedupe_key', 160)->nullable()->unique();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('sent_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->index(['member_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });

        Schema::create('backup_logs', function (Blueprint $table) {
            $table->id();
            $table->string('file_name');
            $table->string('storage_path')->nullable();
            $table->string('status', 20);
            $table->timestampTz('started_at');
            $table->timestampTz('completed_at')->nullable();
            $table->foreignId('initiated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('restored_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('restored_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestampsTz();
        });

        // The constraints below use PostgreSQL-only ALTER TABLE syntax and
        // functions. PHPUnit runs against SQLite, where the tables above are
        // sufficient and these constraints cannot be added after creation.
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE members ADD CONSTRAINT members_birth_or_age_check CHECK (birth_date IS NOT NULL OR age IS NOT NULL)');
        DB::statement("ALTER TABLE members ADD CONSTRAINT members_gender_check CHECK (gender IN ('male','female'))");
        DB::statement("ALTER TABLE members ADD CONSTRAINT members_period_check CHECK (assigned_period IN ('men','women'))");
        DB::statement("ALTER TABLE members ADD CONSTRAINT members_status_check CHECK (status IN ('active','suspended','archived'))");

        DB::statement('ALTER TABLE packages ADD CONSTRAINT packages_duration_check CHECK (duration_value > 0)');
        DB::statement("ALTER TABLE packages ADD CONSTRAINT packages_duration_unit_check CHECK (duration_unit IN ('day','week','month','year'))");
        DB::statement('ALTER TABLE packages ADD CONSTRAINT packages_price_check CHECK ((price_yer IS NULL OR price_yer >= 0) AND (price_sar IS NULL OR price_sar >= 0))');

        DB::statement("ALTER TABLE subscriptions ADD CONSTRAINT subscriptions_period_check CHECK (period IN ('men','women'))");
        DB::statement("ALTER TABLE subscriptions ADD CONSTRAINT subscriptions_currency_check CHECK (currency IN ('YER','SAR'))");
        DB::statement('ALTER TABLE subscriptions ADD CONSTRAINT subscriptions_dates_check CHECK (end_date >= start_date)');
        DB::statement("ALTER TABLE subscriptions ADD CONSTRAINT subscriptions_payment_plan_check CHECK (payment_plan IN ('full','installments'))");
        DB::statement("ALTER TABLE subscriptions ADD CONSTRAINT subscriptions_status_check CHECK (status IN ('upcoming','active','financial_overdue','expiring_soon','expired','cancelled','refunded'))");
        DB::statement('ALTER TABLE subscriptions ADD CONSTRAINT subscriptions_amounts_check CHECK (price_snapshot >= 0 AND discount_amount >= 0 AND final_price >= 0 AND discount_amount <= price_snapshot)');
        DB::statement('ALTER TABLE subscriptions ADD CONSTRAINT subscriptions_installment_count_check CHECK (installment_count >= 1)');
        DB::statement("CREATE UNIQUE INDEX subscriptions_one_current_idx ON subscriptions (member_id) WHERE status IN ('active','financial_overdue','expiring_soon')");

        DB::statement('ALTER TABLE subscription_installments ADD CONSTRAINT subscription_installments_amount_check CHECK (amount >= 0)');
        DB::statement("ALTER TABLE subscription_installments ADD CONSTRAINT subscription_installments_status_check CHECK (status IN ('pending','paid','overdue'))");

        DB::statement('ALTER TABLE subscription_payments ADD CONSTRAINT subscription_payments_amount_check CHECK (amount > 0)');
        DB::statement("ALTER TABLE subscription_payments ADD CONSTRAINT subscription_payments_currency_check CHECK (currency IN ('YER','SAR'))");
        DB::statement("ALTER TABLE subscription_payments ADD CONSTRAINT subscription_payments_method_check CHECK (payment_method IN ('cash','transfer'))");
        DB::statement("ALTER TABLE subscription_payments ADD CONSTRAINT subscription_payments_status_check CHECK (status IN ('completed','reversed'))");

        DB::statement('ALTER TABLE subscription_refunds ADD CONSTRAINT subscription_refunds_amount_check CHECK (amount > 0)');
        DB::statement("ALTER TABLE subscription_refunds ADD CONSTRAINT subscription_refunds_currency_check CHECK (currency IN ('YER','SAR'))");
        DB::statement("ALTER TABLE subscription_refunds ADD CONSTRAINT subscription_refunds_method_check CHECK (payment_method IN ('cash','transfer'))");
        DB::statement("ALTER TABLE subscription_refunds ADD CONSTRAINT subscription_refunds_status_check CHECK (status IN ('completed','reversed'))");

        DB::statement("ALTER TABLE attendances ADD CONSTRAINT attendances_method_check CHECK (method IN ('name','phone','membership_code','barcode','qr'))");
        DB::statement("ALTER TABLE attendance_attempts ADD CONSTRAINT attendance_attempts_method_check CHECK (method IN ('name','phone','membership_code','barcode','qr'))");

        DB::statement('ALTER TABLE expenses ADD CONSTRAINT expenses_amount_check CHECK (amount > 0)');
        DB::statement("ALTER TABLE expenses ADD CONSTRAINT expenses_currency_check CHECK (currency IN ('YER','SAR'))");
        DB::statement("ALTER TABLE expenses ADD CONSTRAINT expenses_method_check CHECK (payment_method IN ('cash','transfer'))");
        DB::statement("ALTER TABLE expenses ADD CONSTRAINT expenses_status_check CHECK (status IN ('pending','approved','cancelled'))");

        DB::statement("ALTER TABLE products ADD CONSTRAINT products_currency_check CHECK (currency IN ('YER','SAR'))");
        DB::statement('ALTER TABLE products ADD CONSTRAINT products_amounts_check CHECK (purchase_cost >= 0 AND selling_price >= 0)');
        DB::statement('ALTER TABLE products ADD CONSTRAINT products_quantity_check CHECK (current_quantity >= 0 AND minimum_quantity >= 0)');
        DB::statement("ALTER TABLE products ADD CONSTRAINT products_status_check CHECK (status IN ('active','inactive'))");

        DB::statement("ALTER TABLE purchases ADD CONSTRAINT purchases_currency_check CHECK (currency IN ('YER','SAR'))");
        DB::statement("ALTER TABLE purchases ADD CONSTRAINT purchases_status_check CHECK (status IN ('pending','approved','cancelled'))");
        DB::statement('ALTER TABLE purchase_items ADD CONSTRAINT purchase_items_values_check CHECK (quantity > 0 AND unit_cost >= 0 AND line_total >= 0)');

        DB::statement("ALTER TABLE sales ADD CONSTRAINT sales_currency_check CHECK (currency IN ('YER','SAR'))");
        DB::statement("ALTER TABLE sales ADD CONSTRAINT sales_discount_type_check CHECK (discount_type IS NULL OR discount_type IN ('amount','percent'))");
        DB::statement("ALTER TABLE sales ADD CONSTRAINT sales_payment_method_check CHECK (payment_method IN ('cash','transfer'))");
        DB::statement("ALTER TABLE sales ADD CONSTRAINT sales_status_check CHECK (status IN ('completed','cancelled'))");
        DB::statement('ALTER TABLE sales ADD CONSTRAINT sales_amounts_check CHECK (subtotal >= 0 AND discount_value >= 0 AND discount_amount >= 0 AND total_amount >= 0)');
        DB::statement('ALTER TABLE sale_items ADD CONSTRAINT sale_items_values_check CHECK (quantity > 0 AND original_unit_price >= 0 AND actual_unit_price >= 0 AND unit_cost >= 0 AND line_total >= 0)');
        DB::statement('ALTER TABLE inventory_movements ADD CONSTRAINT inventory_movements_quantity_check CHECK (quantity_after >= 0)');

        DB::statement('ALTER TABLE nutritionist_schedules ADD CONSTRAINT nutritionist_schedules_day_check CHECK (day_of_week BETWEEN 0 AND 6)');
        DB::statement('ALTER TABLE nutritionist_schedules ADD CONSTRAINT nutritionist_schedules_time_check CHECK (end_time > start_time)');

        DB::statement('ALTER TABLE appointments ADD CONSTRAINT appointments_client_check CHECK (num_nonnulls(member_id, nutrition_client_id) = 1)');
        DB::statement('ALTER TABLE appointments ADD CONSTRAINT appointments_time_check CHECK (end_time > start_time)');
        DB::statement('ALTER TABLE appointments ADD CONSTRAINT appointments_duration_check CHECK (duration_minutes > 0)');
        DB::statement('ALTER TABLE appointments ADD CONSTRAINT appointments_price_check CHECK (price >= 0)');
        DB::statement("ALTER TABLE appointments ADD CONSTRAINT appointments_currency_check CHECK (currency IN ('YER','SAR'))");
        DB::statement("ALTER TABLE appointments ADD CONSTRAINT appointments_status_check CHECK (status IN ('booked','confirmed','completed','cancelled','no_show'))");
        DB::statement("ALTER TABLE appointments ADD CONSTRAINT appointments_payment_status_check CHECK (payment_status IN ('unpaid','paid'))");

        DB::statement('ALTER TABLE appointment_payments ADD CONSTRAINT appointment_payments_amount_check CHECK (amount > 0)');
        DB::statement("ALTER TABLE appointment_payments ADD CONSTRAINT appointment_payments_currency_check CHECK (currency IN ('YER','SAR'))");
        DB::statement("ALTER TABLE appointment_payments ADD CONSTRAINT appointment_payments_method_check CHECK (payment_method IN ('cash','transfer'))");
        DB::statement("ALTER TABLE appointment_payments ADD CONSTRAINT appointment_payments_status_check CHECK (status IN ('paid','reversed'))");

        DB::statement('ALTER TABLE measurements ADD CONSTRAINT measurements_client_check CHECK (num_nonnulls(member_id, nutrition_client_id) = 1)');
        DB::statement('ALTER TABLE measurement_values ADD CONSTRAINT measurement_values_value_check CHECK (value >= 0)');

        DB::statement("ALTER TABLE whatsapp_rules ADD CONSTRAINT whatsapp_rules_type_check CHECK (type IN ('near_expiry','expired','reactivation'))");
        DB::statement("ALTER TABLE whatsapp_rules ADD CONSTRAINT whatsapp_rules_mode_check CHECK (mode IN ('auto','manual'))");
        DB::statement("ALTER TABLE whatsapp_rules ADD CONSTRAINT whatsapp_rules_audience_check CHECK (audience IN ('all','men','women'))");
        DB::statement("ALTER TABLE whatsapp_messages ADD CONSTRAINT whatsapp_messages_status_check CHECK (status IN ('sent','failed','queued'))");
        DB::statement("ALTER TABLE whatsapp_messages ADD CONSTRAINT whatsapp_messages_mode_check CHECK (mode IN ('auto','manual'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_logs');
        Schema::dropIfExists('whatsapp_messages');
        Schema::dropIfExists('whatsapp_rules');
        Schema::dropIfExists('measurement_values');
        Schema::dropIfExists('measurements');
        Schema::dropIfExists('measurement_types');
        Schema::dropIfExists('appointment_payments');
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('nutritionist_schedules');
        Schema::dropIfExists('nutrition_clients');
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchases');
        Schema::dropIfExists('products');
        Schema::dropIfExists('product_categories');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_categories');
        Schema::dropIfExists('attendance_attempts');
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('subscription_refunds');
        Schema::dropIfExists('subscription_payments');
        Schema::dropIfExists('subscription_installments');

        DB::statement('DROP INDEX IF EXISTS subscriptions_one_current_idx');

        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('packages');
        Schema::dropIfExists('members');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('user_permissions');
        Schema::dropIfExists('role_permissions');

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn(['created_by', 'must_change_password', 'last_login_at']);
            $table->string('email')->nullable(false)->change();
        });
    }
};
