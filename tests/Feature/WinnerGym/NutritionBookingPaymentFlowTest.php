<?php

namespace Tests\Feature\WinnerGym;

use App\Models\NutritionClient;
use App\Models\NutritionistSchedule;
use App\Models\Setting;
use App\Models\User;
use App\Services\AppointmentService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class NutritionBookingPaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_external_client_booking_can_be_paid_and_confirmed(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $nutritionist = User::factory()->create(['role' => 'nutritionist']);
        $client = NutritionClient::query()->create([
            'full_name' => 'عميل دورة الحجز والدفع',
            'phone' => '777100001',
            'gender' => 'female',
            'created_by' => $owner->id,
        ]);
        $date = CarbonImmutable::now('Asia/Aden')->addDays(2)->startOfDay();

        NutritionistSchedule::query()->create([
            'nutritionist_id' => $nutritionist->id,
            'day_of_week' => $date->dayOfWeek,
            'start_time' => '16:00',
            'end_time' => '20:00',
            'is_active' => true,
        ]);

        $service = app(AppointmentService::class);
        $appointment = $service->book([
            'nutritionist_id' => $nutritionist->id,
            'member_id' => null,
            'nutrition_client_id' => $client->id,
            'appointment_date' => $date->toDateString(),
            'start_time' => '16:00',
            'duration_minutes' => 30,
            'service_type' => 'consultation',
            'visit_type' => 'in_person',
            'price' => 1500,
            'currency' => 'YER',
            'notes' => 'تحقق شامل لدورة العيادة',
        ], $owner);

        $payment = $service->pay($appointment, [
            'amount' => 1500,
            'currency' => 'YER',
            'payment_method' => 'cash',
            'transfer_reference' => null,
            'proof_path' => null,
        ], $owner);

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ]);
        $this->assertDatabaseHas('appointment_payments', [
            'id' => $payment->id,
            'appointment_id' => $appointment->id,
            'amount' => 1500,
            'currency' => 'YER',
            'payment_method' => 'cash',
            'status' => 'paid',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'category' => 'finance',
            'action' => 'appointment.payment.created',
            'auditable_id' => $payment->id,
        ]);
    }

    public function test_transfer_payment_requires_and_exposes_complete_payment_details(): void
    {
        Storage::fake('local');
        Setting::create(['group' => 'payments', 'key' => 'payments.require_transfer_reference', 'value' => true]);
        Setting::create(['group' => 'payments', 'key' => 'payments.require_proof', 'value' => true]);

        $owner = User::factory()->create(['role' => 'owner']);
        $nutritionist = User::factory()->create(['role' => 'nutritionist']);
        $client = NutritionClient::query()->create([
            'full_name' => 'عميل تحويل العيادة',
            'phone' => '777100002',
            'gender' => 'male',
            'created_by' => $owner->id,
        ]);
        $date = CarbonImmutable::now('Asia/Aden')->addDays(4)->startOfDay();

        NutritionistSchedule::query()->create([
            'nutritionist_id' => $nutritionist->id,
            'day_of_week' => $date->dayOfWeek,
            'start_time' => '16:00',
            'end_time' => '20:00',
            'is_active' => true,
        ]);

        $service = app(AppointmentService::class);
        $appointment = $service->book([
            'nutritionist_id' => $nutritionist->id,
            'member_id' => null,
            'nutrition_client_id' => $client->id,
            'appointment_date' => $date->toDateString(),
            'start_time' => '17:00',
            'duration_minutes' => 30,
            'service_type' => 'consultation',
            'visit_type' => 'in_person',
            'price' => 2000,
            'currency' => 'YER',
        ], $owner);

        try {
            $service->pay($appointment, [
                'amount' => 2000,
                'currency' => 'YER',
                'payment_method' => 'transfer',
                'transfer_service' => 'الكريمي',
                'transfer_reference' => 'NUT-TRANSFER-200',
            ], $owner);
            $this->fail('A required appointment transfer proof was omitted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('proof_path', $exception->errors());
        }

        $proofPath = 'appointment-payment-proofs/proof-200.pdf';
        Storage::disk('local')->put($proofPath, 'appointment-proof');

        $payment = $service->pay($appointment, [
            'amount' => 2000,
            'currency' => 'YER',
            'payment_method' => 'transfer',
            'transfer_service' => 'الكريمي',
            'transfer_reference' => 'NUT-TRANSFER-200',
            'proof_path' => $proofPath,
        ], $owner);

        $this->assertSame('الكريمي', $payment->transfer_service);
        $this->assertSame('NUT-TRANSFER-200', $payment->transfer_reference);
        $this->assertSame($proofPath, $payment->proof_path);

        $this->actingAs($owner)
            ->get(route('appointments.payments.proof', $payment))
            ->assertOk()
            ->assertHeader('cache-control', 'no-store, private');
    }
}
