<?php

namespace Tests\Feature\WinnerGym;

use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrencySeparationTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_return_one_currency_at_a_time(): void
    {
        $service = app(ReportService::class);
        $today = now('Asia/Aden')->toDateString();

        $yer = $service->summary($today, $today, 'all', 'YER');
        $sar = $service->summary($today, $today, 'all', 'SAR');

        $this->assertSame('YER', $yer['currency']);
        $this->assertSame('SAR', $sar['currency']);
    }
}
