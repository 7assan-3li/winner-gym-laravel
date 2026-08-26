<?php

namespace App\Livewire\WhatsApp;

use App\Models\WhatsappRule;
use App\Services\AuditService;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('واتساب - WINNER GYM')]
class Index extends Component
{
    public bool $showEditor = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $trigger = 'near_expiry';

    public int $delay_days = 7;

    public string $message_template = 'مرحبًا {name}، نذكرك باشتراكك في WINNER GYM. كود العضوية: {code}';

    public string $template_name = '';

    public string $template_language = 'ar';

    public string $target_group = 'all';

    public string $mode = 'manual';

    public bool $is_enabled = false;

    public function mount(): void
    {
        abort_unless(auth()->user()?->role === 'owner', 403);
    }

    public function openCreate(): void
    {
        $this->resetEditor();
        $this->showEditor = true;
    }

    public function edit(int $id): void
    {
        $r = WhatsappRule::query()->find($id);
        abort_unless($r !== null, 404);
        $this->editingId = $id;
        $this->name = (string) ($r->name ?? '');
        $this->trigger = (string) ($r->type ?? 'near_expiry');
        $this->delay_days = (int) ($r->days_offset ?? 0);
        $this->message_template = (string) ($r->message_template ?? '');
        $this->template_name = (string) ($r->template_name ?? '');
        $this->template_language = (string) ($r->template_language ?? 'ar');
        $this->target_group = (string) ($r->audience ?? 'all');
        $this->mode = (string) ($r->mode ?? 'manual');
        $this->is_enabled = (bool) ($r->is_enabled ?? false);
        $this->showEditor = true;
    }

    public function save(AuditService $audit): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'trigger' => ['required', 'in:near_expiry,expired,reactivation'],
            'delay_days' => ['required', 'integer', 'min:0', 'max:3650'],
            'message_template' => ['nullable', 'string', 'max:4000'],
            'template_name' => ['nullable', 'string', 'max:255'],
            'template_language' => ['required', 'string', 'max:20'],
            'target_group' => ['required', 'in:all,men,women'],
            'mode' => ['required', 'in:manual,auto'],
            'is_enabled' => ['boolean'],
        ]);

        if (blank($validated['message_template']) && blank($validated['template_name'])) {
            $this->addError('message_template', 'أدخل نص الرسالة أو اسم قالب واتساب معتمد.');

            return;
        }

        $payload = [
            'name' => $validated['name'],
            'type' => $validated['trigger'],
            'days_offset' => $validated['delay_days'],
            'message_template' => $validated['message_template'] ?: null,
            'template_name' => $validated['template_name'] ?: null,
            'template_language' => $validated['template_language'],
            'audience' => $validated['target_group'],
            'mode' => $validated['mode'],
            'is_enabled' => $validated['is_enabled'],
            'duplicate_window_days' => 30,
            'updated_at' => now(),
        ];

        if ($this->editingId) {
            DB::table('whatsapp_rules')->where('id', $this->editingId)->update($payload);
            $audit->log(auth()->user(), 'administration', 'whatsapp_rule.updated', null, null, ['rule_id' => $this->editingId]);
            session()->flash('success', 'تم تحديث قاعدة واتساب.');
        } else {
            $id = DB::table('whatsapp_rules')->insertGetId([
                ...$payload,
                'created_by' => auth()->id(),
                'created_at' => now(),
            ]);
            $audit->log(auth()->user(), 'administration', 'whatsapp_rule.created', null, null, ['rule_id' => $id]);
            session()->flash('success', 'تم إنشاء قاعدة واتساب.');
        }

        $this->showEditor = false;
        $this->resetEditor();
    }

    public function toggle(int $id, AuditService $audit): void
    {
        $rule = WhatsappRule::query()->find($id);
        abort_unless($rule !== null, 404);
        DB::table('whatsapp_rules')->where('id', $id)->update(['is_enabled' => ! (bool) $rule->is_enabled, 'updated_at' => now()]);
        $audit->log(auth()->user(), 'administration', 'whatsapp_rule.toggled', null, null, ['rule_id' => $id]);
    }

    public function delete(int $id, AuditService $audit): void
    {
        if (DB::table('whatsapp_messages')->where('rule_id', $id)->exists()) {
            $this->addError('whatsapp', 'لا يمكن حذف قاعدة لها سجل رسائل. عطّلها بدلًا من ذلك.');

            return;
        }
        DB::table('whatsapp_rules')->where('id', $id)->delete();
        $audit->log(auth()->user(), 'administration', 'whatsapp_rule.deleted', null, null, ['rule_id' => $id]);
    }

    public function run(int $id, WhatsAppService $service): void
    {
        $rule = WhatsappRule::query()->find($id);
        abort_unless($rule !== null, 404);
        $count = $service->runRule($rule, auth()->user());
        session()->flash('success', "اكتملت العملية. الرسائل المرسلة بنجاح: {$count}");
    }

    private function resetEditor(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->trigger = 'near_expiry';
        $this->delay_days = 7;
        $this->message_template = 'مرحبًا {name}، نذكرك باشتراكك في WINNER GYM. كود العضوية: {code}';
        $this->template_name = '';
        $this->template_language = 'ar';
        $this->target_group = 'all';
        $this->mode = 'manual';
        $this->is_enabled = false;
        $this->resetValidation();
    }

    public function render(WhatsAppService $service): View
    {
        return view('livewire.whatsapp.index', [
            'rules' => DB::table('whatsapp_rules')->orderByDesc('is_enabled')->orderByDesc('id')->get(),
            'messages' => DB::table('whatsapp_messages')->orderByDesc('id')->limit(20)->get(),
            'configured' => $service->configured(),
            'stats' => [
                'rules' => DB::table('whatsapp_rules')->count(),
                'active' => DB::table('whatsapp_rules')->where('is_enabled', true)->count(),
                'sent' => DB::table('whatsapp_messages')->where('status', 'sent')->count(),
                'failed' => DB::table('whatsapp_messages')->where('status', 'failed')->count(),
            ],
        ]);
    }
}
