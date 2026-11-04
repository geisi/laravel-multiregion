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
}
