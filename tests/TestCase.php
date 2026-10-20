<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function tearDown(): void
    {
        $storagePath = isset($this->app) ? $this->app->storagePath() : null;
        parent::tearDown();

        $expectedPrefix = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR . 'sipd-tests-' . getmypid() . '-';
        if (
            is_string($storagePath)
            && strpos($storagePath, $expectedPrefix) === 0
            && preg_match('/^[a-f0-9]{16}$/', substr($storagePath, strlen($expectedPrefix)))
        ) {
            (new \Illuminate\Filesystem\Filesystem())->deleteDirectory($storagePath);
        }
    }
}
