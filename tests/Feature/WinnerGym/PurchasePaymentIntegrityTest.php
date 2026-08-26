<?php

namespace Tests\Feature\WinnerGym;

use App\Livewire\Inventory\PurchasesIndex;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Purchase;
use App\Models\Setting;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class PurchasePaymentIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_document_and_transfer_details_are_saved_before_stock_approval(): void
    {
        Storage::fake('local');
        Setting::create(['group' => 'payments', 'key' => 'payments.require_transfer_reference', 'value' => true]);
        Setting::create(['group' => 'payments', 'key' => 'payments.require_proof', 'value' => true]);

        $owner = User::factory()->create(['role' => 'owner']);
        $category = ProductCategory::create(['name' => 'مكملات', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'بروتين اختبار',
            'purchase_cost' => 1000,
            'selling_price' => 1500,
            'currency' => 'YER',
            'current_quantity' => 3,
            'minimum_quantity' => 1,
            'status' => 'active',
        ]);

        Livewire::actingAs($owner)
            ->test(PurchasesIndex::class)
            ->set('purchase_date', now(config('app.timezone'))->toDateString())
            ->set('supplier_name', 'المورد المعتمد')
            ->set('supplier_invoice', 'SUP-INV-300')
            ->set('currency', 'YER')
            ->set('payment_method', 'transfer')
            ->set('transfer_service', 'البسيري')
            ->set('transfer_reference', 'PUR-REF-300')
            ->set('purchase_document', UploadedFile::fake()->create('supplier-invoice.pdf', 150, 'application/pdf'))
            ->set('items', [[
                'product_id' => $product->id,
                'quantity' => 5,
                'unit_cost' => 900,
            ]])
            ->call('create')
            ->assertHasNoErrors();

        $purchase = Purchase::query()->with('items')->sole();

        $this->assertSame('pending', $purchase->status);
        $this->assertSame('البسيري', $purchase->transfer_service);
        $this->assertSame('PUR-REF-300', $purchase->transfer_reference);
        $this->assertNotNull($purchase->proof_path);
        Storage::disk('local')->assertExists($purchase->proof_path);
        $this->assertSame(3, (int) $product->fresh()->current_quantity);

        $this->actingAs($owner)
            ->get(route('inventory.purchases.document', $purchase))
            ->assertOk()
            ->assertHeader('cache-control', 'no-store, private');

        app(InventoryService::class)->approvePurchase($purchase, $owner);

        $this->assertSame('approved', $purchase->fresh()->status);
        $this->assertSame(8, (int) $product->fresh()->current_quantity);
    }
}
