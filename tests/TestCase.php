<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    public function createApplication(): Application
    {
        $app = parent::createApplication();

        if (! $app->environment('testing') || config('database.default') !== 'mysql'
            || config('database.connections.mysql.database') !== 'checkpoint_test') {
            throw new RuntimeException('Tests require the isolated MySQL checkpoint_test database. Clear cached configuration first.');
        }

        return $app;
    }
}
