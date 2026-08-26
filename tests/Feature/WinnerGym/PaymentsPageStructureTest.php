<?php

namespace Tests\Feature\WinnerGym;

use Tests\TestCase;

class PaymentsPageStructureTest extends TestCase
{
    public function test_payments_page_is_focused_on_subscription_collection(): void
    {
        $component = file_get_contents(base_path('app/Livewire/Finance/PaymentsIndex.php'));
        $view = file_get_contents(base_path('resources/views/livewire/finance/payments-index.blade.php'));

        $this->assertStringNotContainsString('App\\Models\\Expense', $component);
        $this->assertStringNotContainsString('App\\Models\\AppointmentPayment', $component);
        $this->assertStringNotContainsString('App\\Models\\Sale', $component);
        $this->assertStringNotContainsString('fin-module-strip', $view);
        $this->assertStringNotContainsString('إضافة مصروف', $view);
        $this->assertStringContainsString("'receivedToday'", $component);
        $this->assertStringContainsString("'overdueAmount'", $component);
        $this->assertStringContainsString('التحصيل حسب طريقة الدفع', $view);
    }

    public function test_payment_actions_and_filters_have_instant_ui_states(): void
    {
        $view = file_get_contents(base_path('resources/views/livewire/finance/payments-index.blade.php'));
        $css = file_get_contents(base_path('public/winner-gym/finance-final.css'));

        $this->assertStringContainsString('x-show="selectorOpen"', $view);
        $this->assertStringContainsString('x-show="payOpen"', $view);
        $this->assertStringContainsString('x-show="reverseOpen"', $view);
        $this->assertStringContainsString('x-show="refundOpen"', $view);
        $this->assertStringContainsString('wire:model.live="filterStatus"', $view);
        $this->assertStringContainsString('wire:model.live="filterMethod"', $view);
        $this->assertStringContainsString('المرجع / سبب العكس', $view);
        $this->assertStringContainsString('.fin-kpis-payments .fin-kpi-value', $css);
        $this->assertStringContainsString('font-size:clamp(16px,1.2vw,21px)', $css);
    }
}
