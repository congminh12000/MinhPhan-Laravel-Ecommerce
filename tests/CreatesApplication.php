<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use RuntimeException;

trait CreatesApplication
{
    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__ . '/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        if ($app->environment('testing')) {
            $defaultConnection = $app['config']->get('database.default');
            $databaseName = (string) $app['config']->get("database.connections.{$defaultConnection}.database");
            $unsafeDatabases = ['beike', 'production', 'prod'];

            if (in_array($databaseName, $unsafeDatabases, true)) {
                throw new RuntimeException(sprintf(
                    'Refusing to run tests against unsafe database "%s". Configure .env.testing to use an isolated test database.',
                    $databaseName
                ));
            }
        }

        return $app;
    }
}
