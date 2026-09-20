<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        if (!extension_loaded('pdo_sqlite') && config('database.default') === 'sqlite') {
            config(['database.default' => 'mysql']);
        }

        parent::setUp();
    }
}
