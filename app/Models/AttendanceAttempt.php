<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceAttempt extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'member_id', 'identifier', 'method', 'allowed', 'rejection_reason',
        'attempted_at', 'recorded_by',
    ];

    protected function casts(): array
    {
        return ['allowed' => 'boolean', 'attempted_at' => 'datetime'];
    }

    /** @return BelongsTo<Member, $this> */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
