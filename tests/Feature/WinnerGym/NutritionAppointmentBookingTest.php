<?php

namespace Tests\Feature\WinnerGym;

use App\Models\NutritionClient;
use App\Models\NutritionistSchedule;
use App\Models\User;
use App\Services\AppointmentService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class NutritionAppointmentBookingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('username')->unique();
            $table->string('email')->nullable();
            $table->string('password');
            $table->string('role');
            $table->string('work_period')->default('both');
            $table->boolean('is_active')->default(true);
            $table->boolean('must_change_password')->default(false);
            $table->timestamps();
        });

        Schema::create('nutrition_clients', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('phone', 30);
            $table->string('gender', 10)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('nutritionist_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('nutritionist_id');
            $table->unsignedTinyInteger('day_of_week');
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('nutritionist_id');
            $table->unsignedBigInteger('member_id')->nullable();
            $table->unsignedBigInteger('nutrition_client_id')->nullable();
            $table->date('appointment_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedSmallInteger('duration_minutes');
            $table->string('service_type', 40)->default('consultation');
            $table->string('visit_type', 20)->default('in_person');
            $table->decimal('price', 18, 2);
            $table->string('currency', 3);
            $table->string('status', 20)->default('booked');
            $table->string('payment_status', 20)->default('unpaid');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
            $table->unique(['nutritionist_id', 'appointment_date', 'start_time']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('category', 40);
            $table->string('action', 120);
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->text('old_values')->nullable();
            $table->text('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->dateTime('created_at');
        });
    }

    public function test_appointment_is_saved_with_clinic_options_and_duplicate_client_booking_is_rejected(): void
    {
        [$owner, $nutritionist, $client, $date] = $this->clinicFixtures();
        $data = $this->bookingData($nutritionist->id, $client->id, $date);

        $appointment = app(AppointmentService::class)->book($data, $owner);

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'service_type' => 'follow_up',
            'visit_type' => 'remote',
            'status' => 'booked',
            'payment_status' => 'unpaid',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'category' => 'nutrition',
            'action' => 'appointment.created',
        ]);

        try {
            app(AppointmentService::class)->book($data, $owner);
            $this->fail('The duplicate client appointment was saved.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('الحجز نفسه', $exception->errors()['appointment'][0]);
        }

        $this->assertDatabaseCount('appointments', 1);
    }

    public function test_overlapping_nutritionist_booking_is_rejected_with_a_clear_message(): void
    {
        [$owner, $nutritionist, $client, $date] = $this->clinicFixtures();
        app(AppointmentService::class)->book($this->bookingData($nutritionist->id, $client->id, $date), $owner);

        $otherClient = NutritionClient::query()->create([
            'full_name' => 'عميل آخر',
            'phone' => '777000002',
            'gender' => 'male',
            'created_by' => $owner->id,
        ]);
        $overlap = $this->bookingData($nutritionist->id, $otherClient->id, $date);
        $overlap['start_time'] = '10:30';

        try {
            app(AppointmentService::class)->book($overlap, $owner);
            $this->fail('The overlapping nutritionist appointment was saved.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('الأخصائي لديه حجز متداخل', $exception->errors()['appointment'][0]);
        }

        $this->assertDatabaseCount('appointments', 1);
    }

    private function clinicFixtures(): array
    {
        $owner = $this->user('Owner', 'owner', 'owner');
        $nutritionist = $this->user('Nutritionist', 'nutritionist', 'nutritionist');
        $client = NutritionClient::query()->create([
            'full_name' => 'عميل التغذية',
            'phone' => '777000001',
            'gender' => 'female',
            'created_by' => $owner->id,
        ]);
        $date = CarbonImmutable::now('Asia/Aden')->addDays(7)->startOfDay();

        NutritionistSchedule::query()->create([
            'nutritionist_id' => $nutritionist->id,
            'day_of_week' => $date->dayOfWeek,
            'start_time' => '08:00',
            'end_time' => '20:00',
            'is_active' => true,
        ]);

        return [$owner, $nutritionist, $client, $date];
    }

    private function user(string $name, string $username, string $role): User
    {
        return User::query()->create([
            'name' => $name,
            'username' => $username,
            'email' => $username.'@example.test',
            'password' => 'password',
            'role' => $role,
            'work_period' => 'both',
            'is_active' => true,
            'must_change_password' => false,
        ]);
    }

    private function bookingData(int $nutritionistId, int $clientId, CarbonImmutable $date): array
    {
        return [
            'nutritionist_id' => $nutritionistId,
            'member_id' => null,
            'nutrition_client_id' => $clientId,
            'appointment_date' => $date->toDateString(),
            'start_time' => '10:00',
            'duration_minutes' => 60,
            'service_type' => 'follow_up',
            'visit_type' => 'remote',
            'price' => 5000,
            'currency' => 'YER',
            'notes' => 'متابعة أسبوعية',
        ];
    }
}
