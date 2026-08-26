<?php

namespace App\Services;

use App\Models\Measurement;
use App\Models\MeasurementType;
use App\Models\MeasurementValue;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MeasurementService
{
    public function __construct(private AuditService $audit) {}

    /** @param array<string, mixed> $data */
    public function record(array $data, User $actor): Measurement
    {
        $memberId = $data['member_id'] ?? null;
        $clientId = $data['nutrition_client_id'] ?? null;

        if ((bool) $memberId === (bool) $clientId) {
            throw ValidationException::withMessages([
                'client' => 'اختر عضوًا أو عميل تغذية غير عضو، وليس الاثنين.',
            ]);
        }

        $rawValues = $data['values'] ?? [];
        if (! is_array($rawValues)) {
            throw ValidationException::withMessages(['values' => 'صيغة قيم القياسات غير صحيحة.']);
        }

        $normalizedValues = [];
        foreach ($rawValues as $code => $value) {
            if (! is_string($code) || $value === null || $value === '') {
                continue;
            }

            if (! is_numeric($value)) {
                throw ValidationException::withMessages(['values' => 'قيم القياسات يجب أن تكون أرقامًا.']);
            }

            $normalizedValues[$code] = (float) $value;
        }

        return DB::transaction(function () use ($data, $actor, $memberId, $clientId, $normalizedValues) {
            $values = collect($normalizedValues);

            $types = MeasurementType::query()
                ->whereIn('code', $values->keys()->all())
                ->where('is_active', true)
                ->get()
                ->keyBy('code');

            $weight = $values->get('weight');
            $heightCm = $values->get('height');
            $bmi = null;

            if ($weight && $heightCm && $heightCm > 0) {
                $heightM = $heightCm / 100;
                $bmi = round($weight / ($heightM * $heightM), 2);
            }

            $measurement = Measurement::create([
                'appointment_id' => $data['appointment_id'] ?? null,
                'member_id' => $memberId,
                'nutrition_client_id' => $clientId,
                'nutritionist_id' => $data['nutritionist_id'] ?? $actor->id,
                'bmi' => $bmi,
                'notes' => $data['notes'] ?? null,
                'measured_at' => now(),
            ]);

            foreach ($values as $code => $value) {
                $type = $types->get($code);
                if (! $type) {
                    continue;
                }

                if ($value < 0) {
                    throw ValidationException::withMessages(['values' => 'قيم القياسات لا يمكن أن تكون سالبة.']);
                }

                MeasurementValue::create([
                    'measurement_id' => $measurement->id,
                    'measurement_type_id' => $type->id,
                    'value' => $value,
                ]);
            }

            $this->audit->log($actor, 'nutrition', 'measurement.created', $measurement);

            return $measurement->load('values.type');
        });
    }
}
