<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CreateGymOwner extends Command
{
    protected $signature = 'winner-gym:create-owner
        {username? : Unique sign-in username}
        {--name= : Owner full name}
        {--email= : Optional owner email}
        {--password= : Set an explicit password}
        {--generate-password : Generate a strong one-time password}';

    protected $description = 'Securely create or reset a WINNER GYM owner account';

    public function handle(): int
    {
        $username = trim((string) ($this->argument('username') ?: $this->ask('اسم المستخدم')));
        $name = trim((string) ($this->option('name') ?: 'المالك العام'));
        $email = trim((string) ($this->option('email') ?? '')) ?: null;
        $explicitPassword = $this->option('password');
        $generated = (bool) $this->option('generate-password');
        $password = $explicitPassword ?: ($generated ? Str::password(20) : (string) $this->secret('كلمة المرور القوية'));

        $existing = User::where('username', $username)->first();
        if ($existing) {
            $existing->update([
                'name' => $name ?: $existing->name,
                'password' => Hash::make($password),
                'role' => 'owner',
                'work_period' => 'both',
                'is_active' => true,
                'must_change_password' => false,
            ]);
            $this->info("تم تحديث حساب المالك: {$existing->username}");
            $this->info("كلمة المرور: {$password}");

            return self::SUCCESS;
        }

        $validator = Validator::make([
            'username' => $username,
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ], [
            'username' => ['required', 'string', 'min:3', 'max:80', 'regex:/^[A-Za-z0-9._-]+$/', 'unique:users,username'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'min:6'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $owner = User::create([
            'name' => $name,
            'username' => $username,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'owner',
            'work_period' => 'both',
            'is_active' => true,
            'must_change_password' => $generated,
        ]);

        $this->info("تم إنشاء حساب المالك: {$owner->username}");
        if ($generated) {
            $this->warn('كلمة المرور المؤقتة (تُعرض مرة واحدة): '.$password);
            $this->warn('سيُطلب تغييرها بعد تسجيل الدخول.');
        }

        return self::SUCCESS;
    }
}
