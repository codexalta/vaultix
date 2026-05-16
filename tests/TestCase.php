<?php

namespace Codexalta\Vaultix\Tests;

use Codexalta\Vaultix\VaultixServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        // Run migrations for testing
        $this->loadMigrationsFrom(__DIR__ . '/../src/Database/Migrations');
    }

    protected function getPackageProviders($app)
    {
        return [
            VaultixServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
        
        // Set super admin for testing
        $app['config']->set('vaultix.super_admin', 'test@example.com');
    }
}
