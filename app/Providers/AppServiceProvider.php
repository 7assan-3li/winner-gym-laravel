<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        Gate::before(function ($user, $ability) {
            return $user->role === 'owner' ? true : null;
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            $this->usesProtectedDatabase(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    /**
     * Never allow destructive Artisan commands against a remote database.
     * The desktop application runs in the local environment while its real
     * data lives on Supabase, so checking only APP_ENV=production is unsafe.
     */
    private function usesProtectedDatabase(): bool
    {
        $default = (string) config('database.default');
        $connection = (array) config("database.connections.{$default}", []);
        $url = (string) ($connection['url'] ?? '');
        $host = strtolower((string) ($connection['host'] ?? ''));

        if ($host === '' && $url !== '') {
            $host = strtolower((string) (parse_url($url, PHP_URL_HOST) ?: ''));
        }

        $isRemotePostgres = $default === 'pgsql'
            && $host !== ''
            && ! in_array($host, ['localhost', '127.0.0.1', '::1'], true);

        return app()->isProduction() || $isRemotePostgres;
    }
}
