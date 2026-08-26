<?php

namespace Tests\Feature\WinnerGym;

use App\Livewire\Inventory\ProductsIndex;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class ProductCreationTest extends TestCase
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

        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id');
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
            $table->timestamps();
        });

        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->string('movement_type', 30);
            $table->integer('quantity_delta');
            $table->integer('quantity_before');
            $table->integer('quantity_after');
            $table->decimal('unit_cost', 18, 2)->nullable();
            $table->string('reference_type', 40)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('created_at');
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

    public function test_product_with_new_category_and_opening_stock_is_saved_atomically(): void
    {
        $owner = User::query()->create([
            'name' => 'Owner',
            'username' => 'owner',
            'email' => 'owner@example.test',
            'password' => 'password',
            'role' => 'owner',
            'work_period' => 'both',
            'is_active' => true,
            'must_change_password' => false,
        ]);

        Livewire::actingAs($owner)
            ->test(ProductsIndex::class)
            ->set('name', 'مياه معدنية 500 مل')
            ->set('new_category_name', 'مشروبات')
            ->set('purchase_cost', '100')
            ->set('selling_price', '150')
            ->set('currency', 'YER')
            ->set('minimum_quantity', 3)
            ->set('opening_quantity', 12)
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('product_categories', [
            'name' => 'مشروبات',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('products', [
            'name' => 'مياه معدنية 500 مل',
            'current_quantity' => 12,
            'purchase_cost' => 100,
            'selling_price' => 150,
            'currency' => 'YER',
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'movement_type' => 'opening_balance',
            'quantity_delta' => 12,
            'quantity_before' => 0,
            'quantity_after' => 12,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'category' => 'inventory',
            'action' => 'product.created',
        ]);
    }
}
