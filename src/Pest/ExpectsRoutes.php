<?php

declare(strict_types=1);

namespace Tpetry\MultiRegion\Pest;

trait ExpectsRoutes
{
    public function expectRoutes(): RouteExpectation
    {
        return new RouteExpectation(app());
    }
}
