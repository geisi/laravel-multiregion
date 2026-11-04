<?php

declare(strict_types=1);

namespace Tpetry\MultiRegion\Pest;

use Illuminate\Contracts\Foundation\Application;

class RouteExpectation
{
    public function __construct(
        protected Application $app,
    ) {}

    /**
     * Assert that every route has multiRegionRead(), multiRegionWrite() or noMultiRegion() applied. The resolved
     * middleware is checked, so middleware groups, aliases and withoutMiddleware() are respected.
     */
    public function toUseMultiRegionMiddleware(): PendingMultiRegionMiddlewareExpectation
    {
        return new PendingMultiRegionMiddlewareExpectation($this->app);
    }
}
