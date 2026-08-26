<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** @property string $status */
class AppointmentPayment extends Model
{
    protected $fillable = [
        'appointment_id', 'amount', 'currency', 'payment_method', 'transfer_service', 'transfer_reference',
        'proof_path', 'status', 'paid_at', 'created_by', 'reversed_by',
        'reversed_at', 'reversal_reason',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'paid_at' => 'datetime', 'reversed_at' => 'datetime'];
    }

    /** @return BelongsTo<Appointment, $this> */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }
}
