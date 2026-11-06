<?php

declare(strict_types=1);

namespace Tpetry\MultiRegion\Testing;

use Illuminate\Support\Testing\Fakes\Fake;
use PHPUnit\Framework\Assert as PHPUnit;
use Tpetry\MultiRegion\Routing\RouteInspector;
use Tpetry\MultiRegion\Routing\RouteMode;

class RouteInspectorFake extends RouteInspector implements Fake
{
    /**
     * The additional ignore patterns passed to every inspect() call.
     *
     * @var list<list<string>>
     */
    protected array $inspectCalled = [];

    /**
     * @param  list<array{methods: list<string>, uri: string, name: ?string, mode: RouteMode, ignoredBy: ?string}>  $routes
     */
    public function __construct(
        protected array $routes,
    ) {}

    /**
     * @param  list<string>  $ignoring
     * @return list<array{methods: list<string>, uri: string, name: ?string, mode: RouteMode, ignoredBy: ?string}>
     */
    public function inspect(array $ignoring = []): array
    {
        $this->inspectCalled[] = $ignoring;

        return $this->routes;
    }

    /**
     * Assert that inspect() was called with the additional ignore patterns.
     *
     * @param  list<string>  $ignoring
     */
    public function assertInspectedWith(array $ignoring): void
    {
        PHPUnit::assertContains($ignoring, $this->inspectCalled, 'inspect() was not called with the ignore patterns ['.implode(', ', $ignoring).'].');
    }
}
