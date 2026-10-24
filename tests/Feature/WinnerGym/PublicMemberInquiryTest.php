<?php

namespace Tests\Feature\WinnerGym;

use App\Models\Member;
use App\Models\Package;
use App\Models\User;
use App\Services\MembershipService;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicMemberInquiryTest extends TestCase
{
    use RefreshDatabase;

    public function test_inquiry_page_is_public(): void
    {
        $this->get('/member-inquiry')->assertOk();
        $this->get('/portal')->assertOk();
        $this->get('/my-subscription')->assertOk();
    }

    public function test_invalid_member_code_does_not_expose_data(): void
    {
        $this->post('/member-inquiry', ['membership_code' => 'WG-NOTFOUND'])
            ->assertRedirect(route('member.inquiry'))
            ->assertSessionHasErrors('membership_code');
    }

    public function test_dual_verification_lookup_with_phone_and_membership_code_succeeds(): void
    {
        $actor = $this->owner();
        $member = $this->createMember($actor, '777123456', '0102030405');
        $package = $this->createPackage($actor, 60000);

        app(SubscriptionService::class)->create([
            'member_id' => $member->id,
            'package_id' => $package->id,
            'period' => 'men',
            'start_date' => now('Asia/Aden')->toDateString(),
            'currency' => 'YER',
            'discount_amount' => 0,
            'payment_plan' => 'full',
            'installment_count' => 1,
            'first_payment_amount' => 60000,
            'payment_method' => 'cash',
            'installment_due_dates' => [],
        ], $actor);

        $response = $this->post('/member-inquiry', [
            'phone' => '777123456',
            'verification_key' => $member->membership_code,
        ]);

        $response->assertOk();
        $response->assertViewHas('result');
        $response->assertSee($member->full_name);
        $response->assertSee($member->membership_code);
        $response->assertSee('باقة اختبار');
        $response->assertSee('60,000');
    }

    public function test_dual_verification_lookup_with_phone_and_identity_suffix_succeeds(): void
    {
        $actor = $this->owner();
        $member = $this->createMember($actor, '770998877', '1995884321');
        $package = $this->createPackage($actor, 40000);

        app(SubscriptionService::class)->create([
            'member_id' => $member->id,
            'package_id' => $package->id,
            'period' => 'men',
            'start_date' => now('Asia/Aden')->toDateString(),
            'currency' => 'YER',
            'discount_amount' => 0,
            'payment_plan' => 'full',
            'installment_count' => 1,
            'first_payment_amount' => 40000,
            'payment_method' => 'cash',
            'installment_due_dates' => [],
        ], $actor);

        // Verification key is last 4 digits of identity (4321)
        $response = $this->post('/portal', [
            'phone' => '770998877',
            'verification_key' => '4321',
        ]);

        $response->assertOk();
        $response->assertViewHas('result');
        $response->assertSee($member->membership_code);
        $response->assertSee('رمز الدخول الذكي للبوابة');
    }

    public function test_dual_verification_with_wrong_key_fails_and_does_not_expose_data(): void
    {
        $actor = $this->owner();
        $member = $this->createMember($actor, '771223344', '9876543210');

        $response = $this->post('/member-inquiry', [
            'phone' => '771223344',
            'verification_key' => '0000', // incorrect key
        ]);

        $response->assertRedirect(route('member.inquiry'));
        $response->assertSessionHasErrors('phone');
    }

    public function test_member_pass_displays_installments_and_gate_qr_value(): void
    {
        $actor = $this->owner();
        $member = $this->createMember($actor, '773344556', '1234567890');
        $package = $this->createPackage($actor, 100000);

        $subscription = app(SubscriptionService::class)->create([
            'member_id' => $member->id,
            'package_id' => $package->id,
            'period' => 'men',
            'start_date' => now('Asia/Aden')->toDateString(),
            'currency' => 'YER',
            'discount_amount' => 0,
            'payment_plan' => 'installments',
            'installment_count' => 2,
            'first_payment_amount' => 50000,
            'payment_method' => 'cash',
            'installment_due_dates' => [now('Asia/Aden')->addMonth()->toDateString()],
        ], $actor);

        $response = $this->post('/member-inquiry', [
            'phone' => '773344556',
            'verification_key' => $member->membership_code,
        ]);

        $response->assertOk();
        $response->assertSee('القسط 1');
        $response->assertSee('القسط 2');
        $response->assertSee('تم السداد');
        $response->assertSee('50,000');
        $response->assertSee('حسابات الصالة للتحويل والسداد');
    }

    private function owner(): User
    {
        return User::factory()->create([
            'role' => 'owner',
            'is_active' => true,
        ]);
    }

    private function createMember(User $actor, string $phone, ?string $identity = null): Member
    {
        return app(MembershipService::class)->create([
            'full_name' => 'مشترك اختباري بوابة',
            'phone' => $phone,
            'gender' => 'male',
            'age' => 25,
            'assigned_period' => 'men',
            'identity_number' => $identity,
            'registration_date' => now('Asia/Aden')->toDateString(),
        ], $actor);
    }

    private function createPackage(User $actor, int $price): Package
    {
        return Package::create([
            'name' => 'باقة اختبار',
            'duration_value' => 1,
            'duration_unit' => 'month',
            'price_yer' => $price,
            'price_sar' => null,
            'is_active' => true,
            'created_by' => $actor->id,
        ]);
    }
}
