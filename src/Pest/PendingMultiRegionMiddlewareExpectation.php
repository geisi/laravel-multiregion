<?php

declare(strict_types=1);

namespace Tpetry\MultiRegion\Pest;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Routing\Router;
use Illuminate\Support\Str;
use PHPUnit\Framework\Assert as PHPUnit;
use Tpetry\MultiRegion\Middleware\MultiRegionRead;
use Tpetry\MultiRegion\Middleware\MultiRegionWrite;
use Tpetry\MultiRegion\Middleware\NoMultiRegion;

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
     * may contain wildcards.
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
        $this->app->make(HttpKernel::class);
        $router = $this->app->make(Router::class);
        $ignoredUris = array_filter($this->ignoring, fn (string $pattern): bool => str_starts_with($pattern, '/'));
        $ignoredNames = array_diff($this->ignoring, $ignoredUris);

        foreach ($router->getRoutes()->getRoutes() as $route) {
            // Laravel stores the URIs without the leading slash ("posts"), except for the root route ("/"). So it is
            // trimmed before being added for comparison, otherwise the root route would become "//".
            $uri = '/'.ltrim($route->uri(), '/');
            $name = $route->getName();
            if (Str::is($ignoredUris, $uri) || ($name !== null && Str::is($ignoredNames, $name))) {
                continue;
            }

            if (array_intersect([MultiRegionRead::class, MultiRegionWrite::class, NoMultiRegion::class], $router->gatherRouteMiddleware($route)) === []) {
                PHPUnit::fail(match ($name) {
                    null => "Expecting {$uri} to use multi-region middleware.",
                    default => "Expecting {$uri} ({$name}) to use multi-region middleware.",
                });
            }

            PHPUnit::assertTrue(true); // @phpstan-ignore staticMethod.alreadyNarrowedType
        }
    }
}
