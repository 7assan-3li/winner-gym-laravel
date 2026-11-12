<?php

namespace App\Livewire\Auth;

use App\Services\AuditService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class ChangePasswordModal extends Component
{
    public bool $isOpen = false;

    public bool $isForced = false;

    public string $current_password = '';

    public string $new_password = '';

    public string $new_password_confirmation = '';

    public bool $showPasswords = false;

    public function mount(): void
    {
        $user = Auth::user();
        if ($user && $user->must_change_password) {
            $this->isForced = true;
            $this->isOpen = true;
        }
    }

    #[On('wg-open-change-password')]
    public function open(): void
    {
        $this->resetValidation();
        $this->reset(['current_password', 'new_password', 'new_password_confirmation', 'showPasswords']);
        $this->isForced = (bool) Auth::user()?->must_change_password;
        $this->isOpen = true;
    }

    public function close(): void
    {
        if ($this->isForced) {
            return;
        }

        $this->resetValidation();
        $this->reset(['current_password', 'new_password', 'new_password_confirmation', 'showPasswords']);
        $this->isOpen = false;
    }

    public function save(AuditService $audit): void
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        $rules = [
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ];

        $messages = [
            'new_password.required' => 'حقل كلمة المرور الجديدة مطلوب.',
            'new_password.min' => 'كلمة المرور الجديدة يجب ألا تقل عن 8 خانات.',
            'new_password.confirmed' => 'تأكيد كلمة المرور الجديدة غير متطابق.',
        ];

        if (! $this->isForced) {
            $rules['current_password'] = ['required', 'string'];
            $messages['current_password.required'] = 'حقل كلمة المرور الحالية مطلوب.';
        }

        $this->validate($rules, $messages);

        if (! $this->isForced) {
            if (! Hash::check($this->current_password, (string) $user->password)) {
                $this->addError('current_password', 'كلمة المرور الحالية غير صحيحة.');

                return;
            }
        }

        $user->update([
            'password' => Hash::make($this->new_password),
            'must_change_password' => false,
        ]);

        Auth::login($user);

        $audit->log($user, 'security', 'user.password_changed', $user);

        $this->reset(['current_password', 'new_password', 'new_password_confirmation', 'showPasswords']);
        $this->isOpen = false;
        $this->isForced = false;

        $this->dispatch('wg-notify', [
            'type' => 'success',
            'message' => 'تم تغيير كلمة المرور بنجاح.',
        ]);
        session()->flash('password_success', 'تم تغيير كلمة المرور بنجاح.');
    }

    public function render(): View
    {
        return view('livewire.auth.change-password-modal');
    }
}
