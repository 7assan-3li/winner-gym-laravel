<?php

use App\Livewire\Finance\ExpensesIndex;
use App\Livewire\Finance\PaymentsIndex;
use App\Livewire\Inventory\ProductsIndex;
use App\Livewire\Inventory\PurchasesIndex;
use App\Livewire\Inventory\SalesIndex;
use App\Livewire\Nutrition\AppointmentsIndex;
use App\Livewire\Nutrition\MeasurementsIndex;
use App\Models\AppointmentPayment;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SubscriptionPayment;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::middleware('auth')->group(function () {
    Route::livewire('/payments', PaymentsIndex::class)->middleware('gym.any:payments.view,payments.create,payments.reverse,refunds.process')->name('payments.index');
    Route::get('/payments/{payment}/proof', function (SubscriptionPayment $payment) {
        abort_unless(filled($payment->proof_path), 404);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($payment->proof_path), 404);

        $extension = pathinfo($payment->proof_path, PATHINFO_EXTENSION);

        return $disk->response(
            $payment->proof_path,
            'subscription-payment-'.$payment->id.($extension ? '.'.$extension : ''),
            ['Cache-Control' => 'private, no-store'],
        );
    })->middleware('gym.any:payments.view,payments.create,payments.reverse,refunds.process,subscriptions.view,subscriptions.manage')->name('payments.proof');

    Route::get('/appointments/payments/{payment}/proof', function (AppointmentPayment $payment) {
        abort_unless(filled($payment->proof_path), 404);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($payment->proof_path), 404);
        $extension = pathinfo($payment->proof_path, PATHINFO_EXTENSION);

        return $disk->response(
            $payment->proof_path,
            'appointment-payment-'.$payment->id.($extension ? '.'.$extension : ''),
            ['Cache-Control' => 'private, no-store'],
        );
    })->middleware('gym.any:payments.view,payments.create,payments.reverse,appointments.manage')->name('appointments.payments.proof');

    Route::livewire('/expenses', ExpensesIndex::class)->middleware('gym.any:expenses.view,expenses.manage')->name('expenses.index');
    Route::get('/expenses/{expense}/receipt', function (Expense $expense) {
        abort_unless(filled($expense->receipt_path), 404);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($expense->receipt_path), 404);

        $extension = pathinfo($expense->receipt_path, PATHINFO_EXTENSION);

        return $disk->response(
            $expense->receipt_path,
            'expense-'.$expense->id.($extension ? '.'.$extension : ''),
            ['Cache-Control' => 'private, no-store'],
        );
    })->middleware('gym.any:expenses.view,expenses.manage')->name('expenses.receipt');

    Route::get('/inventory/product-image/{product}', function (Product $product) {
        abort_unless(filled($product->image_path), 404);

        foreach (['public', 'local'] as $diskName) {
            $disk = Storage::disk($diskName);
            if ($disk->exists($product->image_path)) {
                return $disk->response($product->image_path, null, ['Cache-Control' => 'private, max-age=3600']);
            }
        }

        abort(404);
    })->middleware('gym.any:products.view,products.manage,inventory.view,inventory.manage,sales.view,sales.create,purchases.view,purchases.manage')->name('inventory.product-image');

    Route::livewire('/inventory/products', ProductsIndex::class)->middleware('gym.any:products.view,products.manage,inventory.view,inventory.manage,sales.create')->name('inventory.products');
    Route::get('/inventory/purchases/{purchase}/document', function (Purchase $purchase) {
        abort_unless(filled($purchase->proof_path), 404);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($purchase->proof_path), 404);
        $extension = pathinfo($purchase->proof_path, PATHINFO_EXTENSION);

        return $disk->response(
            $purchase->proof_path,
            'purchase-'.$purchase->id.($extension ? '.'.$extension : ''),
            ['Cache-Control' => 'private, no-store'],
        );
    })->middleware('gym.any:purchases.view,purchases.manage,inventory.manage')->name('inventory.purchases.document');

    Route::livewire('/inventory/purchases', PurchasesIndex::class)->middleware('gym.any:purchases.view,purchases.manage,inventory.manage')->name('inventory.purchases');
    Route::get('/inventory/sales/{sale}/receipt', function (Sale $sale) {
        $sale->load(['member', 'items.product']);

        return view('inventory.sale-receipt', compact('sale'));
    })->middleware('gym.any:sales.view,sales.create,sales.cancel,inventory.manage')->name('inventory.sales.receipt');

    Route::get('/inventory/sales/{sale}/proof', function (Sale $sale) {
        abort_unless(filled($sale->proof_path), 404);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($sale->proof_path), 404);
        $extension = pathinfo($sale->proof_path, PATHINFO_EXTENSION);

        return $disk->response(
            $sale->proof_path,
            'sale-payment-'.$sale->id.($extension ? '.'.$extension : ''),
            ['Cache-Control' => 'private, no-store'],
        );
    })->middleware('gym.any:sales.view,sales.create,sales.cancel,inventory.manage')->name('inventory.sales.proof');

    Route::livewire('/inventory/sales', SalesIndex::class)->middleware('gym.any:sales.view,sales.create,sales.cancel,inventory.manage')->name('inventory.sales');

    Route::livewire('/nutrition/appointments', AppointmentsIndex::class)->middleware('gym.any:appointments.view,appointments.create,appointments.manage,appointments.update_unpaid,appointments.own,appointments.complete_own,appointments.cancel_unpaid_own,nutrition.view')->name('nutrition.appointments');
    Route::livewire('/nutrition/measurements', MeasurementsIndex::class)->middleware('gym.any:measurements.own,appointments.manage,nutrition.view')->name('nutrition.measurements');
});
