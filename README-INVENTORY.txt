WINNER GYM - Inventory Reference Patch

Files changed:
- app/Livewire/Inventory/ProductsIndex.php
- resources/views/livewire/inventory/products-index.blade.php
- resources/views/layouts/app/sidebar.blade.php
- resources/css/app.css

No migrations. No .env changes.

Business rules preserved:
- No suppliers.
- No product expiry dates.
- New products start with quantity 0.
- Stock increases only through approved purchases.
- Sales cannot create negative stock (handled by InventoryService).
- YER and SAR inventory values stay separate.
- No product hard-delete action.
- Purchase cost/current quantity are not directly editable from product edit modal.
