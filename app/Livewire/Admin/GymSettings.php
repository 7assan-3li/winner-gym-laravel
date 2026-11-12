<?php

namespace App\Livewire\Admin;

use App\Services\AuditService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('إعدادات النظام - WINNER GYM')]
class GymSettings extends Component
{
    use WithFileUploads;

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

    public string $commercial_register = '';

    public string $report_header_mode = 'structured';

    public string $report_header_title_en = 'WINNER GYM';

    public string $report_tagline = 'صالة اللياقة وبناء الأجسام';

    public ?string $report_logo_path = null;

    public ?string $report_header_image_path = null;

    public string $report_footer_mode = 'structured';

    public ?string $report_footer_image_path = null;

    public string $report_footer_text = 'هذا التقرير معتمد ومستخرج آلياً من نظام WINNER GYM';

    public bool $report_show_signatures = true;

    public bool $report_show_print_meta = true;

    /** @var mixed */
    public $logo_upload = null;

    /** @var mixed */
    public $header_image_upload = null;

    /** @var mixed */
    public $footer_image_upload = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->role === 'owner', 403);

        $this->gym_name = (string) $this->setting('gym.name', 'WINNER GYM');
        $this->location = (string) $this->setting('gym.location', 'المكلا، اليمن');
        $this->phone = (string) $this->setting('gym.phone', '');
        $this->manager_name = (string) $this->setting('gym.manager_name', '');
        $this->commercial_register = (string) $this->setting('gym.commercial_register', '');
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

        $this->report_header_mode = (string) $this->setting('report.header_mode', 'structured');
        $this->report_header_title_en = (string) $this->setting('report.header_title_en', 'WINNER GYM');
        $this->report_tagline = (string) $this->setting('report.tagline', 'صالة اللياقة وبناء الأجسام');
        $this->report_logo_path = $this->setting('report.logo_path');
        $this->report_header_image_path = $this->setting('report.header_image_path');
        $this->report_footer_mode = (string) $this->setting('report.footer_mode', 'structured');
        $this->report_footer_image_path = $this->setting('report.footer_image_path');
        $this->report_footer_text = (string) $this->setting('report.footer_text', 'هذا التقرير معتمد ومستخرج آلياً من نظام WINNER GYM');
        $this->report_show_signatures = (bool) $this->setting('report.show_signatures', true);
        $this->report_show_print_meta = (bool) $this->setting('report.show_print_meta', true);
    }

    public function removeLogo(): void
    {
        if ($this->report_logo_path) {
            Storage::disk('public')->delete($this->report_logo_path);
            $this->report_logo_path = null;
            $this->put('report.logo_path', null);
        }
        $this->logo_upload = null;
    }

    public function removeHeaderImage(): void
    {
        if ($this->report_header_image_path) {
            Storage::disk('public')->delete($this->report_header_image_path);
            $this->report_header_image_path = null;
            $this->put('report.header_image_path', null);
        }
        $this->header_image_upload = null;
    }

    public function removeFooterImage(): void
    {
        if ($this->report_footer_image_path) {
            Storage::disk('public')->delete($this->report_footer_image_path);
            $this->report_footer_image_path = null;
            $this->put('report.footer_image_path', null);
        }
        $this->footer_image_upload = null;
    }

    public function save(AuditService $audit): void
    {
        abort_unless(auth()->user()?->role === 'owner', 403);

        $this->validate([
            'gym_name' => ['required', 'string', 'max:255'],
            'location' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'manager_name' => ['nullable', 'string', 'max:255'],
            'commercial_register' => ['nullable', 'string', 'max:100'],
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
            'report_header_mode' => ['required', 'in:structured,custom_image'],
            'report_header_title_en' => ['nullable', 'string', 'max:150'],
            'report_tagline' => ['nullable', 'string', 'max:255'],
            'report_footer_mode' => ['required', 'in:structured,custom_image'],
            'report_footer_text' => ['nullable', 'string', 'max:500'],
            'report_show_signatures' => ['boolean'],
            'report_show_print_meta' => ['boolean'],
            'logo_upload' => ['nullable', 'image', 'mimes:jpeg,png,webp,jpg', 'max:2048'],
            'header_image_upload' => ['nullable', 'image', 'mimes:jpeg,png,webp,jpg', 'max:2048'],
            'footer_image_upload' => ['nullable', 'image', 'mimes:jpeg,png,webp,jpg', 'max:2048'],
        ]);

        if ($this->logo_upload) {
            if ($this->report_logo_path) {
                Storage::disk('public')->delete($this->report_logo_path);
            }
            $this->report_logo_path = $this->logo_upload->store('branding', 'public');
            $this->logo_upload = null;
        }

        if ($this->header_image_upload) {
            if ($this->report_header_image_path) {
                Storage::disk('public')->delete($this->report_header_image_path);
            }
            $this->report_header_image_path = $this->header_image_upload->store('branding', 'public');
            $this->header_image_upload = null;
        }

        if ($this->footer_image_upload) {
            if ($this->report_footer_image_path) {
                Storage::disk('public')->delete($this->report_footer_image_path);
            }
            $this->report_footer_image_path = $this->footer_image_upload->store('branding', 'public');
            $this->footer_image_upload = null;
        }

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
        $this->put('gym.commercial_register', $this->commercial_register ?: null);
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

        $this->put('report.header_mode', $this->report_header_mode);
        $this->put('report.header_title_en', $this->report_header_title_en);
        $this->put('report.tagline', $this->report_tagline);
        $this->put('report.logo_path', $this->report_logo_path);
        $this->put('report.header_image_path', $this->report_header_image_path);
        $this->put('report.footer_mode', $this->report_footer_mode);
        $this->put('report.footer_image_path', $this->report_footer_image_path);
        $this->put('report.footer_text', $this->report_footer_text);
        $this->put('report.show_signatures', $this->report_show_signatures);
        $this->put('report.show_print_meta', $this->report_show_print_meta);

        $audit->log(auth()->user(), 'administration', 'settings.updated', null);
        session()->flash('success', 'تم حفظ إعدادات النظام وكليشة التقارير بنجاح.');
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

        Cache::forget('winner-gym:setting:'.$key);
    }

    public function render(): View
    {
        return view('livewire.admin.gym-settings');
    }
}
