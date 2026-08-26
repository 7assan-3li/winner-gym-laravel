<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class PaymentPolicy
{
    public function __construct(private SettingsService $settings) {}

    public function requiresTransferReference(): bool
    {
        return (bool) $this->settings->get('payments.require_transfer_reference', true);
    }

    public function requiresProof(): bool
    {
        return (bool) $this->settings->get('payments.require_proof', false);
    }

    /** @param array<string, mixed> $data */
    public function validate(array $data): void
    {
        $method = $data['payment_method'] ?? null;

        if (! in_array($method, ['cash', 'transfer'], true)) {
            throw ValidationException::withMessages([
                'payment_method' => 'طريقة الدفع غير صحيحة.',
            ]);
        }

        if ($method !== 'transfer') {
            return;
        }

        $errors = [];

        if (blank($data['transfer_service'] ?? null)) {
            $errors['transfer_service'] = 'اسم خدمة التحويل أو الصرافة مطلوب.';
        }

        if ($this->requiresTransferReference() && blank($data['transfer_reference'] ?? null)) {
            $errors['transfer_reference'] = 'رقم مرجع التحويل مطلوب حسب سياسة الدفع.';
        }

        if ($this->requiresProof() && blank($data['proof_path'] ?? null)) {
            $errors['proof_path'] = 'يجب إرفاق سند التحويل حسب سياسة الدفع.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
