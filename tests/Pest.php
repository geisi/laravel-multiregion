<?php

declare(strict_types=1);

use Illuminate\Routing\Router;
use Tpetry\MultiRegion\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

/**
 * Get the resolved middleware (aliases, groups and excluded middleware applied) of a named route.
 *
 * @return list<mixed>
 */
function routeMiddleware(string $name): array
{
    $router = app(Router::class);
    $router->getRoutes()->refreshNameLookups();

    $route = $router->getRoutes()->getByName($name);
    if ($route === null) {
        throw new InvalidArgumentException("The route [{$name}] does not exist.");
    }

    return array_values($router->gatherRouteMiddleware($route));
}
