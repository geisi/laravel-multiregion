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
     * ($this->get('/posts')). So every test needs a fully configured driver, for simplicity the redirect driver is
     * configured. Where specific driver behavior is tested, these settings are overridden.
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set([
            'multiregion.driver' => 'redirect',
            'multiregion.drivers.redirect.url.read' => 'https://replica.test',
            'multiregion.drivers.redirect.url.write' => 'https://primary.test',
        ]);
    }
}
