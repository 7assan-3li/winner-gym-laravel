<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $appointment_date
 * @property string $start_time
 * @property string $end_time
 * @property string $status
 * @property string $payment_status
 * @property-read AppointmentPayment|null $payment
 */
class Appointment extends Model
{
    protected $fillable = [
        'nutritionist_id', 'member_id', 'nutrition_client_id', 'appointment_date',
        'start_time', 'end_time', 'duration_minutes', 'service_type', 'visit_type', 'price', 'currency', 'status',
        'payment_status', 'notes', 'created_by', 'completed_at', 'cancelled_by',
        'cancelled_at', 'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'appointment_date' => 'date', 'price' => 'decimal:2',
            'completed_at' => 'datetime', 'cancelled_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function nutritionist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nutritionist_id');
    }

    /** @return BelongsTo<Member, $this> */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /** @return BelongsTo<NutritionClient, $this> */
    public function nutritionClient(): BelongsTo
    {
        return $this->belongsTo(NutritionClient::class);
    }

    /** @return HasOne<AppointmentPayment, $this> */
    public function payment(): HasOne
    {
        return $this->hasOne(AppointmentPayment::class);
    }

    /** @return HasMany<Measurement, $this> */
    public function measurements(): HasMany
    {
        return $this->hasMany(Measurement::class);
    }
}
