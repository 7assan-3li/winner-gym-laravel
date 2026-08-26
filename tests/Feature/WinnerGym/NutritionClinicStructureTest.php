<?php

namespace Tests\Feature\WinnerGym;

use Tests\TestCase;

class NutritionClinicStructureTest extends TestCase
{
    public function test_nutrition_clinic_contains_fast_actions_finance_modals_and_directories(): void
    {
        $view = file_get_contents(resource_path('views/livewire/nutrition/appointments-index.blade.php'));

        $this->assertStringContainsString('wg-nut-finance-style', $view);
        $this->assertStringContainsString("\$wire.entangle('showBookingModal')", $view);
        $this->assertStringContainsString('wg-nut-action-menu', $view);
        $this->assertStringContainsString('fin-modal large wg-nut-fin-modal', $view);
        $this->assertStringContainsString('service_type', $view);
        $this->assertStringContainsString('visit_type', $view);
        $this->assertStringContainsString('حفظ وتأكيد الحجز', $view);
        $this->assertStringContainsString('عملاء التغذية الخاصون', $view);
        $this->assertStringContainsString('اختصاصيو التغذية', $view);
        $this->assertStringContainsString('جاري حفظ الموعد', $view);
    }

    public function test_nutrition_styles_include_finance_parity_and_mobile_rules(): void
    {
        $css = file_get_contents(public_path('winner-gym/nutrition-final.css'));

        $this->assertStringContainsString('Nutrition clinic / finance visual parity v2', $css);
        $this->assertStringContainsString('.wg-nut-clinic-directory', $css);
        $this->assertStringContainsString('.wg-nut-service-options', $css);
        $this->assertStringContainsString('grid-template-areas:"page" "search"', $css);
    }
}
