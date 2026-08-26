<?php

namespace Tests\Feature\WinnerGym;

use App\Models\Package;
use App\Models\User;
use App\Models\WhatsappRule;
use App\Services\MembershipService;
use App\Services\SubscriptionService;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_rule_uses_canonical_schema_and_deduplicates_successful_messages(): void
    {
        config()->set('services.whatsapp.token', 'test-token');
        config()->set('services.whatsapp.phone_number_id', '123456');
        config()->set('services.whatsapp.graph_version', 'v23.0');

        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'messages' => [['id' => 'wamid.test-1']],
            ]),
        ]);

        $owner = User::factory()->create(['role' => 'owner']);
        $member = app(MembershipService::class)->create([
            'full_name' => 'عضو تذكير واتساب',
            'phone' => '+967 777 300 001',
            'gender' => 'male',
            'age' => 29,
            'assigned_period' => 'men',
            'registration_date' => now(config('app.timezone'))->toDateString(),
        ], $owner);
        $package = Package::create([
            'name' => 'باقة تذكير',
            'duration_value' => 7,
            'duration_unit' => 'day',
            'price_yer' => 7000,
            'is_active' => true,
            'created_by' => $owner->id,
        ]);

        app(SubscriptionService::class)->create([
            'member_id' => $member->id,
            'package_id' => $package->id,
            'period' => 'men',
            'start_date' => now(config('app.timezone'))->toDateString(),
            'currency' => 'YER',
            'discount_amount' => 0,
            'payment_plan' => 'full',
            'installment_count' => 1,
            'first_payment_amount' => 7000,
            'payment_method' => 'cash',
            'installment_due_dates' => [],
        ], $owner);

        $rule = WhatsappRule::create([
            'name' => 'قبل الانتهاء بسبعة أيام',
            'type' => 'near_expiry',
            'days_offset' => 6,
            'message_template' => 'مرحبًا {name}، رقم عضويتك {code}',
            'is_enabled' => true,
            'mode' => 'auto',
            'audience' => 'men',
            'duplicate_window_days' => 30,
            'created_by' => $owner->id,
        ]);

        $service = app(WhatsAppService::class);

        $this->assertSame(1, $service->runRule($rule, $owner));
        $this->assertSame(0, $service->runRule($rule->fresh(), $owner));

        $this->assertDatabaseHas('whatsapp_messages', [
            'rule_id' => $rule->id,
            'member_id' => $member->id,
            'status' => 'sent',
            'provider_message_id' => 'wamid.test-1',
        ]);
        $this->assertDatabaseCount('whatsapp_messages', 1);
        $this->assertNotNull($rule->fresh()->last_run_at);

        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request['to'] === '967777300001');
    }
}
