<?php

declare(strict_types=1);

namespace Tpetry\MultiRegion\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Tpetry\MultiRegion\MultiRegionServiceProvider;
use Tpetry\MultiRegion\Pest\ExpectsRoutes;

abstract class TestCase extends Orchestra
{
    use ExpectsRoutes;

    protected function getPackageProviders($app): array
    {
        return [
            MultiRegionServiceProvider::class,
        ];
    }

    /**
     * The driver is resolved for every generated URL, including the ones Laravel's test helpers generate for requests
     * ($this->get('/posts')). So every test needs a driver, tests for other drivers configure them explicitly.
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('multiregion.driver', 'redirect');
    }
}
