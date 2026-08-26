<?php

namespace Tests\Feature\WinnerGym;

use App\Livewire\Inventory\SalesIndex;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class InventorySaleIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_rejects_unauthorized_empty_duplicate_and_inactive_sales(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $receptionist = User::factory()->create(['role' => 'receptionist']);
        $product = $this->product();
        $service = app(InventoryService::class);

        try {
            $service->createSale($this->saleData($product), $receptionist);
            $this->fail('Unauthorized staff created a sale through the service.');
        } catch (AuthorizationException) {
            $this->assertDatabaseCount('sales', 0);
        }

        foreach ([
            [],
            [
                ['product_id' => $product->id, 'quantity' => 1, 'actual_unit_price' => null],
                ['product_id' => $product->id, 'quantity' => 1, 'actual_unit_price' => null],
            ],
        ] as $items) {
            try {
                $data = $this->saleData($product);
                $data['items'] = $items;
                $service->createSale($data, $owner);
                $this->fail('Invalid sale items were accepted.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('items', $exception->errors());
            }
        }

        $product->update(['status' => 'inactive']);
        try {
            $service->createSale($this->saleData($product), $owner);
            $this->fail('An inactive product was sold.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('product', $exception->errors());
        }

        $this->assertDatabaseCount('sales', 0);
        $this->assertSame(10, (int) $product->fresh()->current_quantity);
    }

    public function test_transfer_policy_is_enforced_and_payment_details_are_linked_to_stock(): void
    {
        Setting::create(['group' => 'payments', 'key' => 'payments.require_transfer_reference', 'value' => true]);
        Setting::create(['group' => 'payments', 'key' => 'payments.require_proof', 'value' => true]);

        $owner = User::factory()->create(['role' => 'owner']);
        $product = $this->product();
        $service = app(InventoryService::class);
        $data = $this->saleData($product);
        $data['payment_method'] = 'transfer';
        $data['transfer_service'] = 'الكريمي';
        $data['transfer_reference'] = 'SALE-TRANSFER-100';

        try {
            $service->createSale($data, $owner);
            $this->fail('A required transfer proof was omitted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('proof_path', $exception->errors());
        }

        $data['proof_path'] = 'sale-payment-proofs/proof-100.pdf';
        $sale = $service->createSale($data, $owner);

        $this->assertSame('transfer', $sale->payment_method);
        $this->assertSame('الكريمي', $sale->transfer_service);
        $this->assertSame('SALE-TRANSFER-100', $sale->transfer_reference);
        $this->assertSame('sale-payment-proofs/proof-100.pdf', $sale->proof_path);
        $this->assertSame('800.00', $sale->total_amount);
        $this->assertSame(8, (int) $product->fresh()->current_quantity);
        $this->assertDatabaseHas('inventory_movements', [
            'reference_type' => 'sale',
            'reference_id' => $sale->id,
            'quantity_delta' => -2,
            'quantity_after' => 8,
        ]);
    }

    public function test_sales_screen_uploads_and_protects_transfer_proof(): void
    {
        Storage::fake('local');
        Setting::create(['group' => 'payments', 'key' => 'payments.require_transfer_reference', 'value' => true]);
        Setting::create(['group' => 'payments', 'key' => 'payments.require_proof', 'value' => true]);

        $owner = User::factory()->create(['role' => 'owner']);
        $product = $this->product();

        Livewire::actingAs($owner)
            ->test(SalesIndex::class)
            ->call('addProduct', $product->id)
            ->set('payment_method', 'transfer')
            ->set('transfer_service', 'العمقي')
            ->set('transfer_reference', 'UI-SALE-REF-200')
            ->set('payment_proof', UploadedFile::fake()->create('sale-proof.pdf', 100, 'application/pdf'))
            ->call('completeSale')
            ->assertHasNoErrors();

        $sale = Sale::query()->sole();

        $this->assertSame('UI-SALE-REF-200', $sale->transfer_reference);
        $this->assertNotNull($sale->proof_path);
        Storage::disk('local')->assertExists($sale->proof_path);

        $this->actingAs($owner)
            ->get(route('inventory.sales.proof', $sale))
            ->assertOk()
            ->assertHeader('cache-control', 'no-store, private');
    }

    private function product(): Product
    {
        $category = ProductCategory::create(['name' => 'مشروبات', 'is_active' => true]);

        return Product::create([
            'category_id' => $category->id,
            'name' => 'مياه اختبار',
            'purchase_cost' => 250,
            'selling_price' => 400,
            'currency' => 'YER',
            'current_quantity' => 10,
            'minimum_quantity' => 2,
            'status' => 'active',
        ]);
    }

    private function saleData(Product $product): array
    {
        return [
            'currency' => 'YER',
            'payment_method' => 'cash',
            'discount_type' => null,
            'discount_value' => 0,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2, 'actual_unit_price' => null],
            ],
        ];
    }
}
