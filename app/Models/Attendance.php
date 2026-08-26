<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $attendance_date
 * @property Carbon $entered_at
 * @property-read Member $member
 * @property-read Subscription $subscription
 */
class Attendance extends Model
{
    protected $fillable = [
        'member_id', 'subscription_id', 'attendance_date', 'entered_at', 'method', 'recorded_by',
    ];

    protected function casts(): array
    {
        return ['attendance_date' => 'date', 'entered_at' => 'datetime'];
    }

    /** @return BelongsTo<Member, $this> */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /** @return BelongsTo<Subscription, $this> */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /** @return BelongsTo<User, $this> */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
