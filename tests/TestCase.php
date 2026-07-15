<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        $environmentConnection = getenv('DB_CONNECTION') ?: ($_ENV['DB_CONNECTION'] ?? null);
        $environmentDatabase = getenv('DB_DATABASE') ?: ($_ENV['DB_DATABASE'] ?? null);

        if ($environmentConnection === 'mysql' && (! is_string($environmentDatabase) || ! str_ends_with($environmentDatabase, '_testing'))) {
            throw new \RuntimeException("Unsafe MySQL test database [{$environmentDatabase}]. The database name must end with _testing.");
        }

        parent::setUp();

        $connection = (string) config('database.default');
        $database = (string) config("database.connections.{$connection}.database");

        if (app()->environment('testing') && $connection === 'mysql' && ! str_ends_with($database, '_testing')) {
            $this->fail("Unsafe MySQL test database [{$database}]. The database name must end with _testing.");
        }
    }
}
