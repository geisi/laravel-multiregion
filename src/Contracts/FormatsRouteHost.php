<?php

declare(strict_types=1);

namespace Tpetry\MultiRegion\Contracts;

use Illuminate\Routing\Route;

/**
 * Drivers implementing this contract change the root of generated URLs, e.g. to point write routes to the primary
 * region. URLs generated with url() have no route.
 */
interface FormatsRouteHost
{
    public function formatRouteHost(string $root, ?Route $route): string;
}
