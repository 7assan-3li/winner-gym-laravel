<?php

namespace Tests\Feature\WinnerGym;

use Tests\TestCase;

class InventoryPagesStructureTest extends TestCase
{
    public function test_products_support_safe_creation_inline_categories_and_image_preview(): void
    {
        $component = file_get_contents(base_path('app/Livewire/Inventory/ProductsIndex.php'));
        $view = file_get_contents(base_path('resources/views/livewire/inventory/products-index.blade.php'));

        $this->assertStringContainsString('use App\\Models\\InventoryMovement;', $component);
        $this->assertStringContainsString('DB::transaction(function ()', $component);
        $this->assertStringContainsString("'new_category_name' => ['nullable', 'required_without:category_id'", $component);
        $this->assertStringContainsString('ProductCategory::firstOrCreate(', $component);
        $this->assertStringContainsString('x-show="createOpen || editOpen"', $view);
        $this->assertStringContainsString('previewUrl = $event.target.files[0]', $view);
        $this->assertStringContainsString('fin-errors', $view);
        $this->assertStringContainsString('fin-category-suggestions', $view);
        $this->assertStringContainsString('wg-products-finance-style', $view);
        $this->assertStringContainsString('ربح الوحدة', $view);
    }

    public function test_purchases_are_focused_on_traceable_pending_approval(): void
    {
        $component = file_get_contents(base_path('app/Livewire/Inventory/PurchasesIndex.php'));
        $view = file_get_contents(base_path('resources/views/livewire/inventory/purchases-index.blade.php'));

        $this->assertStringNotContainsString('wg-stock-flow', $view);
        $this->assertStringNotContainsString('كيف تعمل المشتريات؟', $view);
        $this->assertStringContainsString("'supplier_name' => ['required'", $component);
        $this->assertStringContainsString("'supplier_invoice' => ['required'", $component);
        $this->assertStringContainsString('حفظ كشراء معلق', $view);
        $this->assertStringContainsString('addRow()', $view);
        $this->assertStringContainsString('x-show="purchaseOpen"', $view);
        $this->assertStringContainsString("get(['id', 'name', 'currency', 'current_quantity', 'purchase_cost'])", $component);
    }

    public function test_sales_payment_switch_is_instant_and_transfer_is_complete(): void
    {
        $component = file_get_contents(base_path('app/Livewire/Inventory/SalesIndex.php'));
        $view = file_get_contents(base_path('resources/views/livewire/inventory/sales-index.blade.php'));

        $this->assertStringNotContainsString('نقطة البيع السريعة (POS)', $view);
        $this->assertStringContainsString("payment = 'cash'", $view);
        $this->assertStringContainsString("payment = 'transfer'", $view);
        $this->assertStringContainsString('wire:model="transfer_service"', $view);
        $this->assertStringContainsString('رقم الحوالة / المرجع', $view);
        $this->assertStringContainsString("public string \$transfer_service = 'العمقي';", $component);
        $this->assertStringContainsString('route(\'inventory.sales.receipt\'', $view);
    }
}
