<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Drop the cached guard instances so the next request re-authenticates
     * from scratch.
     *
     * Guards resolve the user once and keep it for the life of the
     * application instance. A real request gets a fresh application, but a
     * test issuing several requests reuses one, which would otherwise let a
     * revoked token keep working.
     */
    protected function forgetAuthenticatedUser(): void
    {
        $this->app['auth']->forgetGuards();
    }
}
