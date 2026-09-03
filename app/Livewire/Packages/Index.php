<?php

namespace App\Livewire\Packages;

use App\Models\Package;
use App\Support\NumberFormatter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('الباقات - WINNER GYM')]
class Index extends Component
{
    use WithPagination;

    public bool $showModal = false;

    public string $name = '';

    public string $duration_unit = 'month';

    public string $description = '';

    public int $duration_value = 1;

    public ?string $price_yer = null;

    public ?string $price_sar = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->role === 'owner', 403);
        if (request()->boolean('create')) {
            $this->showModal = true;
        }
    }

    public function openCreateModal(): void
    {
        $this->resetValidation();
        $this->reset(['name', 'price_yer', 'price_sar', 'description']);
        $this->duration_value = 1;
        $this->duration_unit = 'month';
        $this->showModal = true;
    }

    public function closeCreateModal(): void
    {
        $this->showModal = false;
    }

    public function create(): void
    {
        $this->price_yer = $this->price_yer !== null ? NumberFormatter::clean($this->price_yer) : null;
        $this->price_sar = $this->price_sar !== null ? NumberFormatter::clean($this->price_sar) : null;

        $d = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'duration_value' => ['required', 'integer', 'min:1'],
            'duration_unit' => ['required', Rule::in(['day', 'week', 'month', 'year'])],
            'price_yer' => ['nullable', 'numeric', 'min:0'],
            'price_sar' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:3000'],
        ]);

        if (($d['price_yer'] ?? null) === null && ($d['price_sar'] ?? null) === null) {
            $this->addError('price_yer', 'حدد سعرًا بعملة واحدة على الأقل.');

            return;
        }

        Package::create([...$d, 'created_by' => auth()->id(), 'is_active' => true]);
        $this->forgetMembersPackageOptions();
        $this->reset(['name', 'price_yer', 'price_sar', 'description']);
        $this->duration_value = 1;
        $this->duration_unit = 'month';
        $this->showModal = false;
        session()->flash('success', 'تمت إضافة الباقة.');
    }

    public function toggle(int $id): void
    {
        $p = Package::findOrFail($id);
        $p->update(['is_active' => ! $p->is_active]);
        $this->forgetMembersPackageOptions();
        session()->flash('success', $p->is_active ? 'تم تفعيل الباقة.' : 'تم إيقاف الباقة.');
    }

    public function render(): View
    {
        $counts = DB::selectOne(
            'select count(*) as total,
                count(*) filter (where is_active = true) as active,
                count(*) filter (where is_active = false) as inactive
             from packages'
        );

        return view('livewire.packages.index', [
            'packages' => Package::latest('id')->paginate(10),
            'counts' => [
                'total' => (int) $counts->total,
                'active' => (int) $counts->active,
                'inactive' => (int) $counts->inactive,
            ],
        ]);
    }

    private function forgetMembersPackageOptions(): void
    {
        Cache::store('file')->forget('winner-gym:members:packages');
        Cache::store('file')->forget('winner-gym:members:packages:v2');
    }
}
