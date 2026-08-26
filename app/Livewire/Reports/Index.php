<?php

namespace App\Livewire\Reports;

use App\Services\ReportService;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('التقارير - WINNER GYM')]
class Index extends Component
{
    public string $from = '';

    public string $to = '';

    public string $gender = 'all';

    public string $currency = 'YER';

    public function mount(): void
    {
        abort_unless(
            auth()->user()?->role === 'owner'
            || auth()->user()?->hasGymPermission('reports.operational')
            || auth()->user()?->hasGymPermission('reports.finance'),
            403
        );

        $this->from = now('Asia/Aden')->startOfMonth()->toDateString();
        $this->to = now('Asia/Aden')->toDateString();
    }

    public function render(ReportService $reports): View
    {
        $this->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
            'gender' => ['required', 'in:all,male,female'],
            'currency' => ['required', 'in:YER,SAR'],
        ]);

        return view('livewire.reports.index', [
            'data' => $reports->summary($this->from, $this->to, $this->gender, $this->currency),
        ]);
    }
}
