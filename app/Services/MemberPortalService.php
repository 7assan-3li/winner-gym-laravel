<?php

namespace App\Services;

use App\Models\Member;
use App\Models\Setting;
use App\Models\Subscription;
use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Carbon\CarbonImmutable;

class MemberPortalService
{
    /**
     * Look up member data using dual verification (phone + code/id) or direct membership code.
     *
     * @param  array<string, mixed>  $credentials
     * @return array<string, mixed>|null
     */
    public function lookup(array $credentials): ?array
    {
        // 1. Direct lookup by membership code (backwards compatibility / direct code search)
        $code = trim((string) ($credentials['membership_code'] ?? ''));
        if ($code !== '' && empty($credentials['phone'])) {
            $member = Member::query()
                ->where('membership_code', $code)
                ->first();

            return $member ? $this->formatMemberPass($member) : null;
        }

        // 2. Dual verification lookup (Phone + Verification Key)
        $phoneInput = trim((string) ($credentials['phone'] ?? ''));
        $verificationKey = trim((string) ($credentials['verification_key'] ?? ''));

        if ($phoneInput === '' || $verificationKey === '') {
            return null;
        }

        $phoneDigits = preg_replace('/\D/', '', $phoneInput) ?: '';
        $localPhone = $phoneDigits;

        // If phone has Yemen international prefix 967, extract local 9 digits
        if (str_starts_with($phoneDigits, '967') && strlen($phoneDigits) >= 12) {
            $localPhone = substr($phoneDigits, 3);
        } elseif (str_starts_with($phoneDigits, '0') && strlen($phoneDigits) === 10) {
            $localPhone = substr($phoneDigits, 1);
        }

        // Candidate members matching phone
        $candidates = Member::query()
            ->where(function ($query) use ($phoneInput, $phoneDigits, $localPhone) {
                $query->where('phone', $phoneInput);

                if ($phoneDigits !== '') {
                    $query->orWhere('phone', $phoneDigits);
                }

                if ($localPhone !== '' && $localPhone !== $phoneDigits) {
                    $query->orWhere('phone', $localPhone)
                        ->orWhere('phone', 'like', '%'.$localPhone);
                }
            })
            ->get();

        if ($candidates->isEmpty()) {
            return null;
        }

        $cleanKey = strtoupper($verificationKey);
        $isNumericKey = ctype_digit($verificationKey);

        foreach ($candidates as $candidate) {
            // Check match with full membership code (e.g. WG-8F42K9)
            if (strtoupper($candidate->membership_code) === $cleanKey) {
                return $this->formatMemberPass($candidate);
            }

            // Check match with suffix of membership code (e.g. 42K9)
            if (str_ends_with(strtoupper($candidate->membership_code), $cleanKey)) {
                return $this->formatMemberPass($candidate);
            }

            // Check match with last 4 digits of identity number (if provided)
            if ($isNumericKey && strlen($verificationKey) >= 4 && filled($candidate->identity_number)) {
                $identityDigits = preg_replace('/\D/', '', (string) $candidate->identity_number) ?: '';
                if (str_ends_with($identityDigits, $verificationKey)) {
                    return $this->formatMemberPass($candidate);
                }
            }

            // Check match with last 4 digits of phone
            if ($isNumericKey && strlen($verificationKey) >= 4) {
                $candidatePhoneDigits = preg_replace('/\D/', '', (string) $candidate->phone) ?: '';
                if (str_ends_with($candidatePhoneDigits, $verificationKey)) {
                    return $this->formatMemberPass($candidate);
                }
            }
        }

        return null;
    }

    /**
     * Format all relevant subscription, financial, attendance, and QR pass data for a member.
     *
     * @return array<string, mixed>
     */
    public function formatMemberPass(Member $member): array
    {
        $timezone = config('app.timezone', 'Asia/Aden');
        $today = CarbonImmutable::now($timezone)->startOfDay();

        // Load latest subscription with installments and payments
        $subscription = Subscription::query()
            ->where('member_id', $member->id)
            ->with([
                'package',
                'installments' => fn ($q) => $q->orderBy('installment_number'),
                'payments' => fn ($q) => $q->whereIn('status', ['completed', 'paid']),
            ])
            ->latest('end_date')
            ->first();

        // Branch and Period information
        $branchName = $member->branch?->name ?? 'الفرع الرئيسي';
        $periodLabel = $member->assigned_period === 'women' ? 'فترة النساء' : 'فترة الرجال';
        $workingHours = $this->getWorkingHoursForPeriod($member->assigned_period);

        // QR Code SVG generation
        $qrValue = $member->qr_value ?: ('winner-gym:'.$member->membership_code);
        $qrSvg = $this->generateQrSvg($qrValue);

        // Subscription calculations
        $subscriptionData = null;

        if ($subscription) {
            $startDate = CarbonImmutable::parse($subscription->start_date, $timezone)->startOfDay();
            $endDate = CarbonImmutable::parse($subscription->end_date, $timezone)->startOfDay();

            $totalDays = max(1, (int) $startDate->diffInDays($endDate));
            $daysRemaining = max(0, (int) $today->diffInDays($endDate, false));
            $elapsedDays = min($totalDays, max(0, (int) $startDate->diffInDays($today)));
            $progressPercent = min(100, max(0, (int) round(($elapsedDays / $totalDays) * 100)));

            $paid = (float) $subscription->payments->sum('amount');
            $finalPrice = (float) $subscription->final_price;
            $remaining = max(0.0, round($finalPrice - $paid, 2));

            // Status label and color schema
            $statusMeta = $this->resolveStatusMeta($subscription->status, $daysRemaining, $today, $startDate, $endDate);

            // Installments breakdown
            $installmentsList = [];
            foreach ($subscription->installments as $inst) {
                $instDueDate = CarbonImmutable::parse($inst->due_date, $timezone)->startOfDay();
                $isOverdue = $inst->status === 'overdue' || ($inst->status === 'pending' && $instDueDate->lt($today));

                $installmentsList[] = [
                    'number' => $inst->installment_number,
                    'amount' => (float) $inst->amount,
                    'due_date' => $instDueDate->format('Y-m-d'),
                    'status' => $inst->status,
                    'is_overdue' => $isOverdue,
                    'is_paid' => $inst->status === 'paid',
                ];
            }

            $canEnter = $member->status === 'active'
                && in_array($subscription->status, ['active', 'expiring_soon'], true)
                && $today->betweenIncluded($startDate, $endDate);

            $subscriptionData = [
                'id' => $subscription->id,
                'package_name' => $subscription->package_name_snapshot,
                'status' => $subscription->status,
                'status_label' => $statusMeta['label'],
                'status_color' => $statusMeta['color'],
                'status_badge' => $statusMeta['badge'],
                'alert_message' => $statusMeta['alert'],
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'total_days' => $totalDays,
                'elapsed_days' => $elapsedDays,
                'days_remaining' => $daysRemaining,
                'progress_percent' => $progressPercent,
                'currency' => $subscription->currency,
                'final_price' => $finalPrice,
                'paid_amount' => $paid,
                'remaining_amount' => $remaining,
                'payment_plan' => $subscription->payment_plan,
                'installments_count' => $subscription->installment_count,
                'installments' => $installmentsList,
                'can_enter' => $canEnter,
            ];
        }

        // Attendance stats
        $totalAttendances = $member->attendances()
            ->when($subscription, fn ($q) => $q->where('subscription_id', $subscription->id))
            ->count();

        $lastAttendance = $member->attendances()->latest('entered_at')->first();

        // Bank Accounts & Remittance info for payment
        $bankAccounts = [
            ['name' => 'شركة العمقي للصرافة', 'account' => '254000000 - صالة وينر جيم'],
            ['name' => 'شركة البسيري للصرافة', 'account' => '301000000 - صالة وينر جيم'],
            ['name' => 'بنك الكريمي للتمويل الأصغر', 'account' => '120000000 - صالة وينر جيم'],
        ];

        return [
            'member' => [
                'id' => $member->id,
                'name' => $member->full_name,
                'short_name' => $this->abbreviateName($member->full_name),
                'membership_code' => $member->membership_code,
                'phone_masked' => $this->maskPhone((string) $member->phone),
                'gender' => $member->gender,
                'gender_label' => $member->gender === 'female' ? 'أنثى' : 'ذكر',
                'assigned_period' => $member->assigned_period,
                'period_label' => $periodLabel,
                'branch_name' => $branchName,
                'working_hours' => $workingHours,
                'status' => $member->status,
                'is_active' => $member->status === 'active',
                'barcode_value' => $member->barcode_value ?: $member->membership_code,
                'qr_value' => $qrValue,
                'qr_svg' => $qrSvg,
            ],
            'subscription' => $subscriptionData,
            'attendance' => [
                'total_in_subscription' => $totalAttendances,
                'last_attended_date' => $lastAttendance ? CarbonImmutable::parse($lastAttendance->entered_at, $timezone)->format('Y-m-d') : null,
                'last_attended_time' => $lastAttendance ? CarbonImmutable::parse($lastAttendance->entered_at, $timezone)->format('h:i A') : null,
                'last_attended_human' => $lastAttendance ? CarbonImmutable::parse($lastAttendance->entered_at, $timezone)->diffForHumans() : 'لا يوجد حضور مسجل',
            ],
            'bank_accounts' => $bankAccounts,
            'gym' => [
                'name' => 'WINNER GYM',
                'location' => 'المكلا، اليمن',
                'whatsapp_number' => '967770000000',
            ],
        ];
    }

    /**
     * Generate an optimized SVG QR code string.
     */
    public function generateQrSvg(string $content, int $size = 220): string
    {
        try {
            if (class_exists(Writer::class)) {
                $renderer = new ImageRenderer(
                    new RendererStyle(
                        $size,
                        1,
                        null,
                        null,
                        Fill::uniformColor(new Rgb(255, 255, 255), new Rgb(2, 8, 19))
                    ),
                    new SvgImageBackEnd
                );

                $writer = new Writer($renderer);
                $svg = $writer->writeString($content);

                return trim(substr($svg, (int) strpos($svg, "\n") + 1));
            }
        } catch (\Throwable) {
            // Fallback handled below
        }

        // Clean SVG placeholder fallback
        return sprintf(
            '<svg viewBox="0 0 200 200" width="%d" height="%d" xmlns="http://www.w3.org/2000/svg"><rect width="200" height="200" fill="#ffffff" rx="12"/><text x="100" y="105" font-family="sans-serif" font-size="14" font-weight="bold" fill="#020813" text-anchor="middle">%s</text></svg>',
            $size,
            $size,
            htmlspecialchars($content)
        );
    }

    /**
     * Resolve status metadata including human label, alert, and colors.
     *
     * @return array{label: string, color: string, badge: string, alert: string|null}
     */
    private function resolveStatusMeta(
        string $status,
        int $daysRemaining,
        CarbonImmutable $today,
        CarbonImmutable $start,
        CarbonImmutable $end
    ): array {
        return match ($status) {
            'active' => [
                'label' => 'اشتراك سارٍ ونشط',
                'color' => 'emerald',
                'badge' => 'سارٍ',
                'alert' => null,
            ],
            'expiring_soon' => [
                'label' => 'ينتهي قريباً (متبقي '.$daysRemaining.' أيام)',
                'color' => 'amber',
                'badge' => 'ينتهي قريباً',
                'alert' => 'اشتراكك ينتهي خلال أيام معدودة. يمكنك التجديد الآن بسلاسة للحفاظ على استمرارية جدول تدريبك.',
            ],
            'financial_overdue' => [
                'label' => 'متأخر مالياً (الدخول موقوف)',
                'color' => 'rose',
                'badge' => 'قسط متأخر',
                'alert' => 'تنبيه: دخول الصالة موقوف مؤقتاً لوجود قسط مستحق متأخر. يرجى سداد القسط في الاستقبال أو عبر التحويل البنكي لإعادة تفعيل الدخول فوراً.',
            ],
            'expired' => [
                'label' => 'اشتراك منتهي',
                'color' => 'zinc',
                'badge' => 'منتهي',
                'alert' => 'انتهت فترة اشتراكك السابق. يسعدنا تجديد عضويتك للاستمرار في تحقيق أهدافك الرياضية.',
            ],
            'upcoming' => [
                'label' => 'اشتراك مجدول قادم',
                'color' => 'sky',
                'badge' => 'يبدأ قريباً',
                'alert' => 'يبدأ هذا الاشتراك رسمياً بتاريخ '.$start->format('Y-m-d').'.',
            ],
            'refunded' => [
                'label' => 'تم استرداد الاشتراك',
                'color' => 'purple',
                'badge' => 'مسترد',
                'alert' => 'تمت تسوية واسترداد هذا الاشتراك.',
            ],
            'cancelled' => [
                'label' => 'اشتراك ملغي',
                'color' => 'red',
                'badge' => 'ملغي',
                'alert' => 'تم إلغاء هذا الاشتراك من قبل إدارة الصالة.',
            ],
            default => [
                'label' => $status,
                'color' => 'zinc',
                'badge' => $status,
                'alert' => null,
            ],
        };
    }

    /**
     * Get human-friendly working hours for period.
     */
    private function getWorkingHoursForPeriod(string $period): string
    {
        $key = $period === 'women' ? 'working_hours.women' : 'working_hours.men';
        $setting = Setting::where('key', $key)->value('value');

        if (is_array($setting) && ! empty($setting['start']) && ! empty($setting['end'])) {
            return $this->formatTime($setting['start']).' - '.$this->formatTime($setting['end']);
        }

        return $period === 'women' ? '08:00 صباحاً - 03:00 مساءً' : '03:30 مساءً - 11:30 مساءً';
    }

    private function formatTime(string $time): string
    {
        try {
            return CarbonImmutable::parse($time)->translatedFormat('h:i A');
        } catch (\Throwable) {
            return $time;
        }
    }

    private function maskPhone(string $phone): string
    {
        if (strlen($phone) < 6) {
            return $phone;
        }

        return substr($phone, 0, 3).' **** '.substr($phone, -3);
    }

    private function abbreviateName(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        if (count($parts) <= 2) {
            return $name;
        }

        return $parts[0].' '.mb_substr($parts[1], 0, 1).'. '.$parts[count($parts) - 1];
    }
}
