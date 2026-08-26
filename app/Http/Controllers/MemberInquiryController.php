<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Subscription;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MemberInquiryController extends Controller
{
    public function show(): View
    {
        return view('public.member-inquiry');
    }

    public function lookup(Request $request): View|RedirectResponse
    {
        $validated = $request->validate([
            'membership_code' => ['required', 'string', 'max:30'],
        ]);

        $member = Member::query()
            ->where('membership_code', trim($validated['membership_code']))
            ->first();

        if (! $member) {
            return redirect()->route('member.inquiry')
                ->withErrors(['membership_code' => 'كود العضوية غير صحيح أو غير موجود.'])
                ->withInput();
        }

        $subscription = Subscription::query()
            ->where('member_id', $member->id)
            ->latest('end_date')
            ->first();

        $result = [
            'name' => $this->abbreviate($member->full_name),
            'membership_code' => $member->membership_code,
            'member_status' => $member->status,
            'subscription_status' => $subscription->status ?? 'لا يوجد اشتراك',
            'package' => $subscription->package_name_snapshot ?? '-',
            'start_date' => $subscription?->start_date->format('Y-m-d'),
            'end_date' => $subscription?->end_date->format('Y-m-d'),
            'days_remaining' => $subscription
                ? max(0, (int) now('Asia/Aden')->startOfDay()->diffInDays($subscription->end_date, false))
                : 0,
            'paid' => $subscription
                ? (float) $subscription->payments()->whereIn('status', ['completed', 'paid'])->sum('amount')
                : 0,
            'remaining' => 0,
            'currency' => $subscription?->currency,
        ];

        if ($subscription) {
            $result['remaining'] = max(0, round((float) $subscription->final_price - $result['paid'], 2));
        }

        return view('public.member-inquiry', compact('result'));
    }

    private function abbreviate(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        if (count($parts) <= 2) {
            return $name;
        }

        return $parts[0].' '.mb_substr($parts[1], 0, 1).'. '.$parts[count($parts) - 1];
    }
}
