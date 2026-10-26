<?php

namespace App\Livewire\Admin;

use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('إعدادات النظام - WINNER GYM')]
class GymSettings extends Component
{
    public string $gym_name = 'WINNER GYM';

    public string $location = 'المكلا، اليمن';

    public string $phone = '';

    public string $manager_name = '';

    public string $timezone = 'Asia/Aden';

    public string $default_currency = 'YER';

    public bool $currency_yer = true;

    public bool $currency_sar = true;

    public bool $whatsapp_enabled = false;

    public bool $require_transfer_reference = true;

    public bool $require_payment_proof = false;

    public bool $member_inquiry_enabled = true;

    public string $nutrition_consultation_yer = '5000';

    public string $nutrition_consultation_sar = '50';

    public string $nutrition_follow_up_yer = '3000';

    public string $nutrition_follow_up_sar = '30';

    public string $nutrition_meal_plan_yer = '4000';

    public string $nutrition_meal_plan_sar = '40';

    public string $nutrition_body_analysis_yer = '2500';

    public string $nutrition_body_analysis_sar = '25';

    public function mount(): void
    {
        abort_unless(auth()->user()?->role === 'owner', 403);

        $this->gym_name = (string) $this->setting('gym.name', 'WINNER GYM');
        $this->location = (string) $this->setting('gym.location', 'المكلا، اليمن');
        $this->phone = (string) $this->setting('gym.phone', '');
        $this->manager_name = (string) $this->setting('gym.manager_name', '');
        $this->timezone = (string) $this->setting('app.timezone', 'Asia/Aden');
        $enabled = (array) $this->setting('currencies.enabled', ['YER', 'SAR']);
        $this->currency_yer = in_array('YER', $enabled, true);
        $this->currency_sar = in_array('SAR', $enabled, true);
        $this->default_currency = (string) $this->setting('currencies.default', 'YER');
        $this->whatsapp_enabled = (bool) $this->setting('whatsapp.enabled', false);
        $this->require_transfer_reference = (bool) $this->setting('payments.require_transfer_reference', true);
        $this->require_payment_proof = (bool) $this->setting('payments.require_proof', false);
        $this->member_inquiry_enabled = (bool) $this->setting('member_inquiry.enabled', true);
        $this->nutrition_consultation_yer = (string) $this->setting('nutrition.pricing.consultation_yer', '5000');
        $this->nutrition_consultation_sar = (string) $this->setting('nutrition.pricing.consultation_sar', '50');
        $this->nutrition_follow_up_yer = (string) $this->setting('nutrition.pricing.follow_up_yer', '3000');
        $this->nutrition_follow_up_sar = (string) $this->setting('nutrition.pricing.follow_up_sar', '30');
        $this->nutrition_meal_plan_yer = (string) $this->setting('nutrition.pricing.meal_plan_yer', '4000');
        $this->nutrition_meal_plan_sar = (string) $this->setting('nutrition.pricing.meal_plan_sar', '40');
        $this->nutrition_body_analysis_yer = (string) $this->setting('nutrition.pricing.body_analysis_yer', '2500');
        $this->nutrition_body_analysis_sar = (string) $this->setting('nutrition.pricing.body_analysis_sar', '25');
    }

    public function save(AuditService $audit): void
    {
        abort_unless(auth()->user()?->role === 'owner', 403);

        $this->validate([
            'gym_name' => ['required', 'string', 'max:255'],
            'location' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'manager_name' => ['nullable', 'string', 'max:255'],
            'timezone' => ['required', 'in:Asia/Aden'],
            'default_currency' => ['required', 'in:YER,SAR'],
            'currency_yer' => ['boolean'],
            'currency_sar' => ['boolean'],
            'whatsapp_enabled' => ['boolean'],
            'require_transfer_reference' => ['boolean'],
            'require_payment_proof' => ['boolean'],
            'member_inquiry_enabled' => ['boolean'],
            'nutrition_consultation_yer' => ['required', 'numeric', 'min:0'],
            'nutrition_consultation_sar' => ['required', 'numeric', 'min:0'],
            'nutrition_follow_up_yer' => ['required', 'numeric', 'min:0'],
            'nutrition_follow_up_sar' => ['required', 'numeric', 'min:0'],
            'nutrition_meal_plan_yer' => ['required', 'numeric', 'min:0'],
            'nutrition_meal_plan_sar' => ['required', 'numeric', 'min:0'],
            'nutrition_body_analysis_yer' => ['required', 'numeric', 'min:0'],
            'nutrition_body_analysis_sar' => ['required', 'numeric', 'min:0'],
        ]);

        $currencies = array_values(array_filter([
            $this->currency_yer ? 'YER' : null,
            $this->currency_sar ? 'SAR' : null,
        ]));
        if ($currencies === []) {
            $this->addError('currency_yer', 'يجب تفعيل عملة واحدة على الأقل.');

            return;
        }
        if (! in_array($this->default_currency, $currencies, true)) {
            $this->default_currency = $currencies[0];
        }

        $this->put('gym.name', $this->gym_name);
        $this->put('gym.location', $this->location);
        $this->put('gym.phone', $this->phone ?: null);
        $this->put('gym.manager_name', $this->manager_name ?: null);
        $this->put('app.timezone', $this->timezone);
        $this->put('currencies.enabled', $currencies);
        $this->put('currencies.default', $this->default_currency);
        $this->put('whatsapp.enabled', $this->whatsapp_enabled);
        $this->put('payments.require_transfer_reference', $this->require_transfer_reference);
        $this->put('payments.require_proof', $this->require_payment_proof);
        $this->put('member_inquiry.enabled', $this->member_inquiry_enabled);
        $this->put('nutrition.pricing.consultation_yer', (float) $this->nutrition_consultation_yer);
        $this->put('nutrition.pricing.consultation_sar', (float) $this->nutrition_consultation_sar);
        $this->put('nutrition.pricing.follow_up_yer', (float) $this->nutrition_follow_up_yer);
        $this->put('nutrition.pricing.follow_up_sar', (float) $this->nutrition_follow_up_sar);
        $this->put('nutrition.pricing.meal_plan_yer', (float) $this->nutrition_meal_plan_yer);
        $this->put('nutrition.pricing.meal_plan_sar', (float) $this->nutrition_meal_plan_sar);
        $this->put('nutrition.pricing.body_analysis_yer', (float) $this->nutrition_body_analysis_yer);
        $this->put('nutrition.pricing.body_analysis_sar', (float) $this->nutrition_body_analysis_sar);

        $audit->log(auth()->user(), 'administration', 'settings.updated', null);
        session()->flash('success', 'تم حفظ إعدادات النظام.');
    }

    private function setting(string $key, mixed $default = null): mixed
    {
        $value = DB::table('settings')->where('key', $key)->value('value');
        if ($value === null) {
            return $default;
        }
        if (is_array($value)) {
            return $value;
        }
        $decoded = json_decode((string) $value, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
    }

    private function put(string $key, mixed $value): void
    {
        DB::table('settings')->updateOrInsert(
            ['key' => $key],
            [
                'group' => str_contains($key, '.') ? explode('.', $key)[0] : 'general',
                'value' => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'updated_by' => auth()->id(),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    public function render(): View
    {
        return view('livewire.admin.gym-settings');
    }
}
