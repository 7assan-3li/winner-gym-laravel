<?php

namespace App\Services;

use App\Models\Member;
use App\Models\User;
use App\Models\WhatsappRule;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class WhatsAppService
{
    public function configured(): bool
    {
        return filled(config('services.whatsapp.token'))
            && filled(config('services.whatsapp.phone_number_id'))
            && filled(config('services.whatsapp.graph_version'));
    }

    /** @return array<string, mixed> */
    public function sendText(string $phone, string $message): array
    {
        if (! $this->configured()) {
            throw new RuntimeException('WhatsApp Cloud API غير مهيأ بعد.');
        }

        $url = sprintf(
            'https://graph.facebook.com/%s/%s/messages',
            config('services.whatsapp.graph_version'),
            config('services.whatsapp.phone_number_id')
        );

        $response = Http::withToken(config('services.whatsapp.token'))
            ->acceptJson()
            ->post($url, [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $this->normalizePhone($phone),
                'type' => 'text',
                'text' => ['preview_url' => false, 'body' => $message],
            ]);

        $response->throw();

        return $this->jsonResponse($response->json());
    }

    /** @return array<string, mixed> */
    public function sendTemplate(string $phone, string $templateName, string $language = 'ar'): array
    {
        if (! $this->configured()) {
            throw new RuntimeException('WhatsApp Cloud API غير مهيأ بعد.');
        }

        $url = sprintf(
            'https://graph.facebook.com/%s/%s/messages',
            config('services.whatsapp.graph_version'),
            config('services.whatsapp.phone_number_id')
        );

        $response = Http::withToken(config('services.whatsapp.token'))
            ->acceptJson()
            ->post($url, [
                'messaging_product' => 'whatsapp',
                'to' => $this->normalizePhone($phone),
                'type' => 'template',
                'template' => [
                    'name' => $templateName,
                    'language' => ['code' => $language],
                ],
            ]);

        $response->throw();

        return $this->jsonResponse($response->json());
    }

    public function runRule(WhatsappRule $rule, ?User $actor = null): int
    {
        if (! $this->configured()) {
            throw new RuntimeException('WhatsApp Cloud API غير مهيأ بعد.');
        }

        $members = $this->eligibleMembers($rule);
        $sent = 0;

        foreach ($members as $member) {
            if (! $member->phone) {
                continue;
            }
            if ($this->recentlySent($rule, (int) $member->id)) {
                continue;
            }

            $message = $this->renderMessage((string) ($rule->message_template ?? ''), $member);

            $messageId = DB::table('whatsapp_messages')->insertGetId([
                'rule_id' => $rule->id,
                'member_id' => $member->id,
                'phone' => $member->phone,
                'message' => $message,
                'status' => 'queued',
                'mode' => $rule->mode ?? 'manual',
                'sent_by' => $actor?->id,
                'created_at' => now(),
            ]);

            try {
                $result = filled($rule->template_name ?? null)
                    ? $this->sendTemplate($member->phone, $rule->template_name, $rule->template_language ?: 'ar')
                    : $this->sendText($member->phone, $message);

                DB::table('whatsapp_messages')->where('id', $messageId)->update([
                    'status' => 'sent',
                    'provider_message_id' => data_get($result, 'messages.0.id'),
                    'sent_at' => now(),
                ]);
                $sent++;
            } catch (\Throwable $e) {
                DB::table('whatsapp_messages')->where('id', $messageId)->update([
                    'status' => 'failed',
                    'error_message' => mb_substr($e->getMessage(), 0, 3000),
                ]);
            }
        }

        DB::table('whatsapp_rules')->where('id', $rule->id)->update([
            'last_run_at' => now(),
            'updated_at' => now(),
        ]);

        return $sent;
    }

    public function runAutoRules(): int
    {
        if (! Schema::hasTable('whatsapp_rules')) {
            return 0;
        }

        $rules = WhatsappRule::query()
            ->where('is_enabled', true)
            ->where('mode', 'auto')
            ->get();

        $sent = 0;
        foreach ($rules as $rule) {
            $sent += $this->runRule($rule);
        }

        return $sent;
    }

    /** @return Collection<int, Member> */
    private function eligibleMembers(WhatsappRule $rule): Collection
    {
        $days = max(0, (int) ($rule->days_offset ?? 0));
        $trigger = (string) $rule->type;

        $q = Member::query()->where('status', 'active')->whereNotNull('phone');

        if ($rule->audience === 'men') {
            $q->where('gender', 'male');
        }
        if ($rule->audience === 'women') {
            $q->where('gender', 'female');
        }

        if (in_array($trigger, ['near_expiry', 'expired', 'reactivation'], true)) {
            $q->whereHas('subscriptions', function ($s) use ($trigger, $days) {
                if ($trigger === 'near_expiry') {
                    $date = now('Asia/Aden')->addDays($days)->toDateString();
                    $s->whereDate('end_date', $date)
                        ->whereIn('status', ['active', 'expiring_soon']);
                } else {
                    $date = now('Asia/Aden')->subDays($days)->toDateString();
                    $s->whereDate('end_date', $date)
                        ->whereIn('status', ['expired', 'cancelled', 'refunded']);
                }
            });
        } else {
            $q->whereRaw('1 = 0');
        }

        return $q->limit(1000)->get();
    }

    private function recentlySent(WhatsappRule $rule, int $memberId): bool
    {
        $days = max(1, (int) $rule->duplicate_window_days);

        return DB::table('whatsapp_messages')
            ->where('rule_id', $rule->id)
            ->where('member_id', $memberId)
            ->whereIn('status', ['sent', 'queued'])
            ->where('created_at', '>=', now()->subDays($days))
            ->exists();
    }

    private function renderMessage(string $template, Member $member): string
    {
        return strtr($template, [
            '{name}' => $member->full_name,
            '{code}' => $member->membership_code,
        ]);
    }

    /** @return array<string, mixed> */
    private function jsonResponse(mixed $payload): array
    {
        if (! is_array($payload)) {
            throw new RuntimeException('استجابة WhatsApp غير صالحة.');
        }

        return $payload;
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?? $phone;
    }
}
