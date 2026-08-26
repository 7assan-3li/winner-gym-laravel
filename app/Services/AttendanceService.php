<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceAttempt;
use App\Models\Member;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    private const CURRENT_STATUSES = ['active', 'financial_overdue', 'expiring_soon'];

    public function __construct(private SubscriptionService $subscriptions) {}

    public function record(string $method, string $identifier, User $actor): Attendance
    {
        $member = $this->findMember($method, $identifier);

        if (! $member) {
            $this->attempt(null, $identifier, $method, false, 'member_not_found', $actor);
            throw ValidationException::withMessages(['member' => 'لم يتم العثور على العضو.']);
        }

        try {
            $result = DB::transaction(function () use ($member, $method, $actor) {
                $member = Member::query()->lockForUpdate()->findOrFail($member->id);
                $now = CarbonImmutable::now('Asia/Aden');
                $date = $now->toDateString();

                if ($member->status !== 'active') {
                    return ['rejected' => 'member_inactive', 'member' => $member];
                }

                if (! $this->insidePeriod($member->assigned_period, $now)) {
                    return ['rejected' => 'outside_period', 'member' => $member];
                }

                $subscription = Subscription::query()
                    ->where('member_id', $member->id)
                    ->whereIn('status', self::CURRENT_STATUSES)
                    ->whereDate('start_date', '<=', $date)
                    ->whereDate('end_date', '>=', $date)
                    ->lockForUpdate()
                    ->latest('end_date')
                    ->first();

                if (! $subscription) {
                    return ['rejected' => 'no_active_subscription', 'member' => $member];
                }

                if ($subscription->period !== $member->assigned_period) {
                    return ['rejected' => 'wrong_subscription_period', 'member' => $member];
                }

                $subscription = $this->subscriptions->refreshFinancialStatus($subscription);

                if ($subscription->status === 'financial_overdue') {
                    return ['rejected' => 'financial_overdue', 'member' => $member];
                }

                $attendance = Attendance::create([
                    'member_id' => $member->id,
                    'subscription_id' => $subscription->id,
                    'attendance_date' => $date,
                    'entered_at' => $now->utc(),
                    'method' => $method,
                    'recorded_by' => $actor->id,
                ]);

                return ['attendance' => $attendance, 'member' => $member];
            });
        } catch (QueryException $e) {
            if ((string) $e->getCode() === '23505') {
                $this->attempt($member, $identifier, $method, false, 'already_attended_today', $actor);
                throw ValidationException::withMessages(['attendance' => 'تم تسجيل حضور العضو اليوم مسبقًا.']);
            }

            throw $e;
        }

        if (isset($result['rejected'])) {
            $this->attempt($result['member'], $identifier, $method, false, $result['rejected'], $actor);

            $messages = [
                'member_inactive' => 'العضو غير نشط.',
                'outside_period' => 'الحضور خارج الفترة المسموحة.',
                'no_active_subscription' => 'لا يوجد اشتراك نشط صالح اليوم.',
                'wrong_subscription_period' => 'فترة الاشتراك لا تطابق فترة العضو.',
                'financial_overdue' => 'الحضور موقوف بسبب قسط متأخر.',
            ];

            throw ValidationException::withMessages([
                'attendance' => $messages[$result['rejected']],
            ]);
        }

        $attendance = $result['attendance'];
        $this->attempt($result['member'], $identifier, $method, true, null, $actor);

        return $attendance;
    }

    private function findMember(string $method, string $identifier): ?Member
    {
        return match ($method) {
            'phone' => Member::where('phone', $identifier)->first(),
            'membership_code' => Member::where('membership_code', $identifier)->first(),
            'barcode' => Member::where('barcode_value', $identifier)->first(),
            'qr' => Member::where('qr_value', $identifier)
                ->orWhere('membership_code', str_replace('winner-gym:', '', $identifier))
                ->first(),
            'name' => Member::where('full_name', $identifier)->first(),
            default => null,
        };
    }

    private function insidePeriod(string $period, CarbonImmutable $now): bool
    {
        $key = $period === 'women' ? 'working_hours.women' : 'working_hours.men';
        $hours = Setting::where('key', $key)->value('value');

        if (! is_array($hours) || empty($hours['start']) || empty($hours['end'])) {
            return true;
        }

        $start = CarbonImmutable::parse($now->toDateString().' '.$hours['start'], 'Asia/Aden');
        $end = CarbonImmutable::parse($now->toDateString().' '.$hours['end'], 'Asia/Aden');

        return $now->betweenIncluded($start, $end);
    }

    private function attempt(
        ?Member $member,
        string $identifier,
        string $method,
        bool $allowed,
        ?string $reason,
        User $actor,
    ): void {
        AttendanceAttempt::create([
            'member_id' => $member?->id,
            'identifier' => $identifier,
            'method' => $method,
            'allowed' => $allowed,
            'rejection_reason' => $reason,
            'attempted_at' => now(),
            'recorded_by' => $actor->id,
        ]);
    }
}
