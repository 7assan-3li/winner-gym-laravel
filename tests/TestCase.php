<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        $connection = $_ENV['DB_CONNECTION'] ?? $_SERVER['DB_CONNECTION'] ?? getenv('DB_CONNECTION') ?: null;
        $database = $_ENV['DB_DATABASE'] ?? $_SERVER['DB_DATABASE'] ?? getenv('DB_DATABASE') ?: null;
        $url = $_ENV['DB_URL'] ?? $_SERVER['DB_URL'] ?? getenv('DB_URL') ?: null;

        if ($connection !== 'sqlite' || $database !== ':memory:' || filled($url)) {
            throw new \RuntimeException(
                'Unsafe test database blocked. PHPUnit must use sqlite with DB_DATABASE=:memory: and an empty DB_URL.'
            );
        }

        parent::setUp();

        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            throw new \RuntimeException('Laravel loaded an unsafe database during tests.');
        }
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
