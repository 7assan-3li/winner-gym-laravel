<?php

namespace Tests\Feature\WinnerGym;

use App\Livewire\Finance\PaymentsIndex;
use App\Livewire\Members\Index as MembersIndex;
use App\Livewire\Subscriptions\Index as SubscriptionsIndex;
use App\Models\Member;
use App\Models\Package;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\SubscriptionInstallment;
use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Services\MembershipService;
use App\Services\PaymentService;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class MemberSubscriptionPaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_full_subscription_and_cash_payment_are_linked_and_audited(): void
    {
        $actor = $this->owner();
        $member = $this->member($actor);
        $package = $this->package($actor, 120000);

        $subscription = app(SubscriptionService::class)->create([
            'member_id' => $member->id,
            'package_id' => $package->id,
            'period' => 'men',
            'start_date' => now('Asia/Aden')->toDateString(),
            'currency' => 'YER',
            'discount_amount' => 20000,
            'payment_plan' => 'full',
            'installment_count' => 1,
            'first_payment_amount' => 100000,
            'payment_method' => 'cash',
            'installment_due_dates' => [],
        ], $actor);

        $payment = $subscription->payments->sole();
        $installment = $subscription->installments->sole();

        $this->assertSame($member->id, $subscription->member_id);
        $this->assertSame($package->id, $subscription->package_id);
        $this->assertSame($subscription->id, $installment->subscription_id);
        $this->assertSame($installment->id, $payment->installment_id);
        $this->assertSame('paid', $installment->status);
        $this->assertSame('100000.00', $payment->amount);
        $this->assertSame('cash', $payment->payment_method);
        $this->assertSame($actor->id, $payment->created_by);
        $this->assertNotEmpty($payment->receipt_number);
        $this->assertSame('0.00', $subscription->remainingAmount());

        $this->assertDatabaseHas('audit_logs', ['action' => 'member.created', 'auditable_id' => $member->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'subscription.created', 'auditable_id' => $subscription->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'subscription.payment.created', 'auditable_id' => $payment->id]);
    }

    public function test_installment_transfer_payments_keep_all_financial_details_and_balances(): void
    {
        $this->requireCompleteTransferDetails();
        $actor = $this->owner();
        $member = $this->member($actor);
        $package = $this->package($actor, 100000);

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
            'payment_method' => 'transfer',
            'transfer_service' => 'العمقي',
            'transfer_reference' => 'FIRST-REF-100',
            'proof_path' => 'subscription-payment-proofs/first-proof.pdf',
            'installment_due_dates' => [now('Asia/Aden')->addMonth()->toDateString()],
        ], $actor);

        $secondInstallment = $subscription->installments->firstWhere('installment_number', 2);
        $secondPayment = app(PaymentService::class)->payInstallment($secondInstallment, [
            'amount' => 50000,
            'currency' => 'YER',
            'payment_method' => 'transfer',
            'transfer_service' => 'الكريمي',
            'transfer_reference' => 'SECOND-REF-200',
            'proof_path' => 'subscription-payment-proofs/second-proof.jpg',
        ], $actor);

        $subscription = $subscription->fresh(['installments', 'payments']);

        $this->assertCount(2, $subscription->installments);
        $this->assertCount(2, $subscription->payments);
        $this->assertTrue($subscription->installments->every(fn (SubscriptionInstallment $row) => $row->status === 'paid'));
        $this->assertSame('100000.00', number_format((float) $subscription->paidAmount(), 2, '.', ''));
        $this->assertSame('0.00', $subscription->remainingAmount());
        $this->assertSame($secondInstallment->id, $secondPayment->installment_id);
        $this->assertSame('الكريمي', $secondPayment->transfer_service);
        $this->assertSame('SECOND-REF-200', $secondPayment->transfer_reference);
        $this->assertSame('subscription-payment-proofs/second-proof.jpg', $secondPayment->proof_path);
        $this->assertSame($actor->id, $secondPayment->created_by);
        $this->assertDatabaseCount('subscription_payments', 2);
        $this->assertDatabaseCount('audit_logs', 4);
    }

    public function test_transfer_policy_is_enforced_inside_payment_service(): void
    {
        $this->requireCompleteTransferDetails();
        $actor = $this->owner();
        $member = $this->member($actor);
        $package = $this->package($actor, 100000);
        $subscription = $this->installmentSubscription($actor, $member, $package);
        $installment = $subscription->installments->firstWhere('installment_number', 2);

        try {
            app(PaymentService::class)->payInstallment($installment, [
                'amount' => 50000,
                'currency' => 'YER',
                'payment_method' => 'transfer',
                'transfer_service' => 'الكريمي',
                'transfer_reference' => 'SECOND-REF-200',
            ], $actor);
            $this->fail('Payment service accepted a transfer without the required proof.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('proof_path', $exception->errors());
        }

        $this->assertSame('pending', $installment->fresh()->status);
        $this->assertDatabaseCount('subscription_payments', 1);
    }

    public function test_finance_form_uploads_and_links_a_transfer_proof_to_the_payment(): void
    {
        Storage::fake('local');
        $this->requireCompleteTransferDetails();
        $actor = $this->owner();
        $member = $this->member($actor);
        $package = $this->package($actor, 100000);
        $subscription = $this->installmentSubscription($actor, $member, $package);
        $installment = $subscription->installments->firstWhere('installment_number', 2);

        Livewire::actingAs($actor)
            ->test(PaymentsIndex::class)
            ->call('selectInstallment', $installment->id)
            ->set('payment_method', 'transfer')
            ->set('transfer_service', 'الكريمي')
            ->set('transfer_reference', 'UI-REF-300')
            ->set('payment_proof', UploadedFile::fake()->image('proof.jpg'))
            ->call('pay')
            ->assertHasNoErrors();

        $payment = SubscriptionPayment::query()->where('installment_id', $installment->id)->sole();

        $this->assertSame('transfer', $payment->payment_method);
        $this->assertSame('الكريمي', $payment->transfer_service);
        $this->assertSame('UI-REF-300', $payment->transfer_reference);
        $this->assertNotNull($payment->proof_path);
        Storage::disk('local')->assertExists($payment->proof_path);
        $this->actingAs($actor)
            ->get(route('payments.proof', $payment))
            ->assertOk()
            ->assertHeader('cache-control', 'no-store, private');
        $this->assertSame($actor->id, $payment->created_by);
        $this->assertSame('paid', $installment->fresh()->status);
    }

    public function test_livewire_member_and_full_subscription_forms_complete_one_linked_operation(): void
    {
        $actor = $this->owner();
        $package = $this->package($actor, 120000);

        Livewire::actingAs($actor)
            ->test(MembersIndex::class)
            ->set('full_name', 'عضو من واجهة النظام')
            ->set('phone', '777000222')
            ->set('gender', 'male')
            ->set('assigned_period', 'men')
            ->set('age', 31)
            ->set('address', 'المكلا')
            ->call('create')
            ->assertHasNoErrors();

        $member = Member::query()->where('phone', '777000222')->sole();

        Livewire::actingAs($actor)
            ->test(SubscriptionsIndex::class)
            ->set('member_id', $member->id)
            ->set('package_id', $package->id)
            ->set('period', 'men')
            ->set('start_date', now('Asia/Aden')->toDateString())
            ->set('currency', 'YER')
            ->set('discount_amount', '20000')
            ->set('payment_plan', 'full')
            ->set('payment_method', 'cash')
            ->call('create')
            ->assertHasNoErrors();

        $subscription = Subscription::query()->with(['installments', 'payments'])->where('member_id', $member->id)->sole();

        $this->assertSame($package->id, $subscription->package_id);
        $this->assertSame('100000.00', $subscription->final_price);
        $this->assertSame('0.00', $subscription->remainingAmount());
        $this->assertSame('paid', $subscription->installments->sole()->status);
        $this->assertSame('cash', $subscription->payments->sole()->payment_method);
        $this->assertSame($actor->id, $subscription->payments->sole()->created_by);
    }

    public function test_disabled_reference_and_proof_settings_are_honored_by_subscription_service(): void
    {
        Setting::create(['group' => 'payments', 'key' => 'payments.require_transfer_reference', 'value' => false]);
        Setting::create(['group' => 'payments', 'key' => 'payments.require_proof', 'value' => false]);
        $actor = $this->owner();
        $member = $this->member($actor);
        $package = $this->package($actor, 100000);

        $subscription = app(SubscriptionService::class)->create([
            'member_id' => $member->id,
            'package_id' => $package->id,
            'period' => 'men',
            'start_date' => now('Asia/Aden')->toDateString(),
            'currency' => 'YER',
            'discount_amount' => 0,
            'payment_plan' => 'full',
            'installment_count' => 1,
            'first_payment_amount' => 100000,
            'payment_method' => 'transfer',
            'transfer_service' => 'الكريمي',
            'installment_due_dates' => [],
        ], $actor);

        $payment = $subscription->payments->sole();

        $this->assertSame('transfer', $payment->payment_method);
        $this->assertSame('الكريمي', $payment->transfer_service);
        $this->assertNull($payment->transfer_reference);
        $this->assertNull($payment->proof_path);
    }

    public function test_subscription_page_saves_proofs_for_initial_and_later_transfer_payments(): void
    {
        Storage::fake('local');
        $this->requireCompleteTransferDetails();
        $actor = $this->owner();
        $member = $this->member($actor);
        $package = $this->package($actor, 100000);
        $dueDate = now('Asia/Aden')->addMonth()->toDateString();

        Livewire::actingAs($actor)
            ->test(SubscriptionsIndex::class)
            ->set('member_id', $member->id)
            ->set('package_id', $package->id)
            ->set('period', 'men')
            ->set('start_date', now('Asia/Aden')->toDateString())
            ->set('currency', 'YER')
            ->set('discount_amount', '0')
            ->set('payment_plan', 'installments')
            ->set('installment_count', 2)
            ->set('installment_due_dates', [$dueDate])
            ->set('first_payment_amount', '50000')
            ->set('payment_method', 'transfer')
            ->set('transfer_service', 'الكريمي')
            ->set('transfer_reference', 'FIRST-LIVEWIRE-REF')
            ->set('payment_proof', UploadedFile::fake()->image('first-proof.jpg'))
            ->call('create')
            ->assertHasNoErrors();

        $subscription = Subscription::query()->with(['installments', 'payments'])->where('member_id', $member->id)->sole();
        $firstPayment = $subscription->payments->sole();
        $secondInstallment = $subscription->installments->firstWhere('installment_number', 2);

        $this->assertSame('FIRST-LIVEWIRE-REF', $firstPayment->transfer_reference);
        $this->assertNotNull($firstPayment->proof_path);
        Storage::disk('local')->assertExists($firstPayment->proof_path);

        Livewire::actingAs($actor)
            ->test(SubscriptionsIndex::class)
            ->call('openCollection', $subscription->id)
            ->set('collectionMethod', 'transfer')
            ->set('collectionTransferService', 'العمقي')
            ->set('collectionTransferReference', 'SECOND-LIVEWIRE-REF')
            ->set('collectionPaymentProof', UploadedFile::fake()->image('second-proof.jpg'))
            ->call('receiveCollection')
            ->assertHasNoErrors();

        $subscription = $subscription->fresh(['installments', 'payments']);
        $secondPayment = $subscription->payments->firstWhere('installment_id', $secondInstallment->id);

        $this->assertCount(2, $subscription->payments);
        $this->assertSame('SECOND-LIVEWIRE-REF', $secondPayment->transfer_reference);
        $this->assertSame('العمقي', $secondPayment->transfer_service);
        $this->assertNotNull($secondPayment->proof_path);
        Storage::disk('local')->assertExists($secondPayment->proof_path);
        $this->assertTrue($subscription->installments->every(fn (SubscriptionInstallment $row) => $row->status === 'paid'));
        $this->assertSame('0.00', $subscription->remainingAmount());
    }

    public function test_financial_guards_prevent_zero_installments_and_changes_after_refund(): void
    {
        $actor = $this->owner();
        $member = $this->member($actor);
        $package = $this->package($actor, 100000);

        try {
            app(SubscriptionService::class)->create([
                'member_id' => $member->id,
                'package_id' => $package->id,
                'period' => 'men',
                'start_date' => now('Asia/Aden')->toDateString(),
                'currency' => 'YER',
                'discount_amount' => 0,
                'payment_plan' => 'installments',
                'installment_count' => 2,
                'first_payment_amount' => 100000,
                'payment_method' => 'cash',
                'installment_due_dates' => [now('Asia/Aden')->addMonth()->toDateString()],
            ], $actor);
            $this->fail('An installment plan was created without a remaining installment balance.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('first_payment_amount', $exception->errors());
        }

        $this->assertDatabaseCount('subscriptions', 0);
        $this->assertDatabaseCount('subscription_installments', 0);
        $this->assertDatabaseCount('subscription_payments', 0);

        $subscription = $this->installmentSubscription($actor, $member, $package);
        $firstPayment = $subscription->payments->sole();
        $secondInstallment = $subscription->installments->firstWhere('installment_number', 2);

        app(PaymentService::class)->refund($subscription, [
            'payment_method' => 'cash',
            'reason' => 'اختبار حماية ترابط السجلات المالية',
        ], $actor);

        try {
            app(PaymentService::class)->reverse($firstPayment, 'محاولة عكس بعد الاسترداد', $actor);
            $this->fail('A payment was reversed after the subscription refund.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('payment', $exception->errors());
        }

        try {
            app(PaymentService::class)->payInstallment($secondInstallment, [
                'amount' => 50000,
                'currency' => 'YER',
                'payment_method' => 'cash',
            ], $actor);
            $this->fail('An installment was paid after the subscription refund.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('installment', $exception->errors());
        }

        $this->assertSame('refunded', $subscription->fresh()->status);
        $this->assertSame('completed', $firstPayment->fresh()->status);
        $this->assertSame('pending', $secondInstallment->fresh()->status);
        $this->assertDatabaseCount('subscription_payments', 1);
    }

    private function owner(): User
    {
        return User::factory()->create(['role' => 'owner']);
    }

    private function member(User $actor): Member
    {
        return app(MembershipService::class)->create([
            'full_name' => 'عضو اختبار مترابط',
            'phone' => '777000111',
            'gender' => 'male',
            'age' => 28,
            'assigned_period' => 'men',
            'registration_date' => now('Asia/Aden')->toDateString(),
        ], $actor);
    }

    private function package(User $actor, int $price): Package
    {
        return Package::create([
            'name' => 'باقة اختبار شهرية',
            'duration_value' => 1,
            'duration_unit' => 'month',
            'price_yer' => $price,
            'price_sar' => null,
            'is_active' => true,
            'created_by' => $actor->id,
        ]);
    }

    private function installmentSubscription(User $actor, Member $member, Package $package): Subscription
    {
        return app(SubscriptionService::class)->create([
            'member_id' => $member->id,
            'package_id' => $package->id,
            'period' => 'men',
            'start_date' => now('Asia/Aden')->toDateString(),
            'currency' => 'YER',
            'discount_amount' => 0,
            'payment_plan' => 'installments',
            'installment_count' => 2,
            'first_payment_amount' => 50000,
            'payment_method' => 'transfer',
            'transfer_service' => 'العمقي',
            'transfer_reference' => 'FIRST-REF-100',
            'proof_path' => 'subscription-payment-proofs/first-proof.pdf',
            'installment_due_dates' => [now('Asia/Aden')->addMonth()->toDateString()],
        ], $actor);
    }

    private function requireCompleteTransferDetails(): void
    {
        Setting::create(['group' => 'payments', 'key' => 'payments.require_transfer_reference', 'value' => true]);
        Setting::create(['group' => 'payments', 'key' => 'payments.require_proof', 'value' => true]);
    }
}
