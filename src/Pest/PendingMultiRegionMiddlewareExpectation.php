<?php

declare(strict_types=1);

namespace Tpetry\MultiRegion\Pest;

use Illuminate\Contracts\Foundation\Application;
use PHPUnit\Framework\Assert as PHPUnit;
use Tpetry\MultiRegion\Facades\RouteInspector;
use Tpetry\MultiRegion\Routing\RouteMode;

class PendingMultiRegionMiddlewareExpectation
{
    /**
     * @var list<string>
     */
    protected array $ignoring = [];

    public function __construct(
        protected Application $app,
    ) {}

    /**
     * Exempt routes by their URI (starting with a slash, e.g. "/storage/*") or by their name (e.g. "crm.page.*"). Both
     * may contain wildcards. They are added to the routes ignored by the configuration.
     *
     * @param  list<string>|string  $patterns
     */
    public function ignoring(array|string $patterns): static
    {
        $this->ignoring = [...$this->ignoring, ...(array) $patterns];

        return $this;
    }

    public function __destruct()
    {
        foreach (RouteInspector::inspect($this->ignoring) as $route) {
            if ($route['mode'] === RouteMode::Missing) {
                PHPUnit::fail(match ($route['name']) {
                    null => "Expecting {$route['uri']} to use multi-region middleware.",
                    default => "Expecting {$route['uri']} ({$route['name']}) to use multi-region middleware.",
                });
            }

            PHPUnit::assertTrue(true); // @phpstan-ignore staticMethod.alreadyNarrowedType
        }
    }
}
