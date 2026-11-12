<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Services\SettingsService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class SubscriptionContractController extends Controller
{
    public function show(Request $request, Subscription $subscription, SettingsService $settings): View
    {
        $subscription->loadMissing(['member', 'package', 'creator']);

        $tz = config('app.timezone', 'Asia/Aden');
        $date = CarbonImmutable::parse($subscription->start_date ?? now(), $tz);
        $date->locale('ar');

        $gymName = (string) $settings->get('gym.name', 'WINNER GYM — صالة وينر الرياضية');
        $gymLocation = (string) $settings->get('gym.location', 'المكلا، اليمن');
        $gymPhone = (string) $settings->get('gym.phone', '—');
        $managerName = (string) $settings->get('gym.manager_name', 'إدارة النادي');
        $commercialRegister = (string) $settings->get('gym.commercial_register', '—');

        $durationUnit = $subscription->duration_unit_snapshot ?: 'month';
        $durationValue = (int) ($subscription->duration_value_snapshot ?: 1);

        $durationUnitText = match ($durationUnit) {
            'day' => 'يوم',
            'week' => 'أسبوع',
            'month' => 'شهر',
            'year' => 'سنة',
            default => 'شهر',
        };

        $durationText = match ($durationUnit) {
            'day' => $durationValue === 1 ? 'يوميًا' : "لمدة {$durationValue} أيام",
            'week' => $durationValue === 1 ? 'أسبوعيًا' : "لمدة {$durationValue} أسابيع",
            'month' => $durationValue === 1 ? 'شهريًا' : "لمدة {$durationValue} أشهر",
            'year' => $durationValue === 1 ? 'سنويًا' : "لمدة {$durationValue} سنوات",
            default => 'شهريًا',
        };

        $periodLabel = $subscription->period === 'women' ? 'فترة النساء' : 'فترة الرجال';

        return view('contracts.subscriber-agreement', [
            'subscription' => $subscription,
            'member' => $subscription->member,
            'dayName' => $date->translatedFormat('l'),
            'dayNumber' => $date->format('d'),
            'monthNumber' => $date->format('m'),
            'yearNumber' => $date->format('Y'),
            'gymName' => $gymName,
            'gymLocation' => $gymLocation,
            'gymPhone' => $gymPhone,
            'managerName' => $managerName,
            'commercialRegister' => $commercialRegister,
            'durationText' => $durationText,
            'durationUnitText' => $durationUnitText,
            'periodLabel' => $periodLabel,
            'autoprint' => $request->boolean('autoprint'),
        ]);
    }
}
