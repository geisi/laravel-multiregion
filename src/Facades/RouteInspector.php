<?php

declare(strict_types=1);

namespace Tpetry\MultiRegion\Facades;

use Illuminate\Support\Facades\Facade;
use Tpetry\MultiRegion\Routing\RouteInspector as RouteInspectorHelper;
use Tpetry\MultiRegion\Routing\RouteMode;
use Tpetry\MultiRegion\Testing\RouteInspectorFake;

/**
 * @method static list<array{methods: list<string>, uri: string, name: ?string, mode: RouteMode, ignoredBy: ?string}> inspect(list<string> $ignoring = [])
 * @method static void assertInspectedWith(list<string> $ignoring)
 *
 * @see RouteInspectorHelper
 * @see RouteInspectorFake
 */
class RouteInspector extends Facade
{
    /**
     * @param  list<array{methods: list<string>, uri: string, name: ?string, mode: RouteMode, ignoredBy: ?string}>  $routes
     */
    public static function fake(array $routes): RouteInspectorFake
    {
        return tap(new RouteInspectorFake($routes), function (RouteInspectorFake $fake) {
            static::swap($fake);
        });
    }

    protected static function getFacadeAccessor(): string
    {
        return RouteInspectorHelper::class;
    }
}
