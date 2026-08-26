<?php

namespace Tests\Feature\WinnerGym;

use App\Models\ExpenseCategory;
use App\Models\Setting;
use App\Models\User;
use App\Services\ExpenseService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ExpenseReceiptRequirementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('username')->unique();
            $table->string('email')->nullable();
            $table->string('password');
            $table->string('role');
            $table->string('work_period')->default('both');
            $table->boolean('is_active')->default(true);
            $table->boolean('must_change_password')->default(false);
            $table->timestamps();
        });

        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id');
            $table->string('title');
            $table->decimal('amount', 18, 2);
            $table->string('currency', 3);
            $table->date('expense_date');
            $table->string('payment_method', 20);
            $table->string('transfer_service')->nullable();
            $table->string('transfer_reference')->nullable();
            $table->string('receipt_path')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('approved');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('group');
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('category', 40);
            $table->string('action', 120);
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->text('old_values')->nullable();
            $table->text('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->dateTime('created_at');
        });
    }

    public function test_expense_cannot_be_created_without_a_receipt(): void
    {
        $owner = $this->owner();
        $category = ExpenseCategory::query()->create(['name' => 'صيانة', 'is_active' => true]);

        try {
            app(ExpenseService::class)->create($this->expenseData($category->id), $owner);
            $this->fail('The expense was created without a receipt.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('receipt', $exception->errors());
        }

        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_expense_is_saved_when_a_receipt_path_is_present(): void
    {
        $owner = $this->owner();
        $category = ExpenseCategory::query()->create(['name' => 'صيانة', 'is_active' => true]);
        $data = $this->expenseData($category->id);
        $data['receipt_path'] = 'expenses/receipts/invoice.pdf';

        $expense = app(ExpenseService::class)->create($data, $owner);

        $this->assertSame('expenses/receipts/invoice.pdf', $expense->receipt_path);
        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'category_id' => $category->id,
            'receipt_path' => 'expenses/receipts/invoice.pdf',
            'status' => 'approved',
        ]);
    }

    public function test_transfer_expense_keeps_service_reference_and_receipt(): void
    {
        Setting::create(['group' => 'payments', 'key' => 'payments.require_transfer_reference', 'value' => true]);
        Setting::create(['group' => 'payments', 'key' => 'payments.require_proof', 'value' => true]);

        $owner = $this->owner();
        $category = ExpenseCategory::query()->create(['name' => 'مورد خدمات', 'is_active' => true]);
        $data = $this->expenseData($category->id);
        $data['payment_method'] = 'transfer';
        $data['transfer_service'] = 'الكريمي';
        $data['transfer_reference'] = 'EXP-REF-400';
        $data['receipt_path'] = 'expenses/receipts/expense-400.pdf';

        $expense = app(ExpenseService::class)->create($data, $owner);

        $this->assertSame('الكريمي', $expense->transfer_service);
        $this->assertSame('EXP-REF-400', $expense->transfer_reference);
        $this->assertSame('expenses/receipts/expense-400.pdf', $expense->receipt_path);
    }

    private function owner(): User
    {
        return User::query()->create([
            'name' => 'Owner',
            'username' => 'owner',
            'email' => 'owner@example.test',
            'password' => 'password',
            'role' => 'owner',
            'work_period' => 'both',
            'is_active' => true,
            'must_change_password' => false,
        ]);
    }

    private function expenseData(int $categoryId): array
    {
        return [
            'category_id' => $categoryId,
            'title' => 'صيانة جهاز',
            'amount' => 2500,
            'currency' => 'YER',
            'expense_date' => now('Asia/Aden')->toDateString(),
            'payment_method' => 'cash',
            'notes' => 'اختبار شرط الفاتورة',
        ];
    }
}
