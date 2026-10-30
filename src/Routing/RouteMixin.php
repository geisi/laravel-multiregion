<?php

declare(strict_types=1);

namespace Tpetry\MultiRegion\Routing;

use Closure;
use Illuminate\Routing\Route;
use LogicException;
use Tpetry\MultiRegion\Middleware\MultiRegionRead;
use Tpetry\MultiRegion\Middleware\MultiRegionWrite;
use Tpetry\MultiRegion\Middleware\NoMultiRegion;

/**
 * @mixin Route
 */
class RouteMixin
{
    /**
     * Apply the MultiRegionRead middleware to the route.
     */
    public function multiRegionRead(): Closure
    {
        return $this->exclusively(MultiRegionRead::class);
    }

    /**
     * Apply the MultiRegionWrite middleware to the route.
     */
    public function multiRegionWrite(): Closure
    {
        return $this->exclusively(MultiRegionWrite::class);
    }

    /**
     * Mark the route as intentionally not using multi-region handling (e.g. routes that don't use the database).
     */
    public function noMultiRegion(): Closure
    {
        return $this->exclusively(NoMultiRegion::class);
    }

    /**
     * Create a macro applying the middleware and excluding every other multi-region middleware. The idea is that cases
     * where `withoutMiddleware` is called on a route group would lead to that middleware not being applied when added
     * to a route inside the group. This is a developer error, but it shouldn't silently lead to incorrect behavior. So
     * the idea is that when a route middleware is added, it is not allowed to be in the exclusion list already. This
     * solves the issue. It also prevents errors like `->multiRegionRead()->multiRegionWrite()` because the first one
     * would add the write middleware to the exclusion list, while the second call would want to add it.
     *
     * @param  class-string  $middleware
     */
    private function exclusively(string $middleware): Closure
    {
        return function () use ($middleware): Route {
            // An excluded middleware can't be applied anymore, e.g. after another macro has been called on the route.
            // Silently ending up without any multi-region middleware would be a hard to find bug, so this fails.
            if (in_array($middleware, $this->excludedMiddleware(), true)) {
                throw new LogicException("The middleware [{$middleware}] can't be applied to the route [{$this->uri()}] as it has been excluded, e.g. by another multi-region macro or withoutMiddleware().");
            }

            $others = array_diff([MultiRegionRead::class, MultiRegionWrite::class, NoMultiRegion::class], [$middleware]);

            return $this->withoutMiddleware(array_values($others))->middleware($middleware);
        };
    }
}
