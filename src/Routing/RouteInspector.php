<?php

declare(strict_types=1);

namespace Tpetry\MultiRegion\Routing;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Routing\Router;
use Illuminate\Support\Str;
use Tpetry\MultiRegion\Middleware\MultiRegionRead;
use Tpetry\MultiRegion\Middleware\MultiRegionWrite;
use Tpetry\MultiRegion\Middleware\NoMultiRegion;

class RouteInspector
{
    public function __construct(
        protected Application $app,
    ) {}

    /**
     * @param  list<string>  $ignoring
     * @return list<array{methods: list<string>, uri: string, name: ?string, mode: RouteMode, ignoredBy: ?string}>
     */
    public function inspect(array $ignoring = []): array
    {
        // The HTTP kernel registers the middleware groups and aliases at the router when it is created. Without it, the
        // middleware can't be resolved when e.g. running in the console.
        $this->app->make(HttpKernel::class);
        $router = $this->app->make(Router::class);

        /** @var list<string> $patterns */
        $patterns = [...$this->app->make('config')->array('multiregion.routes.ignore', []), ...$ignoring];

        $routes = [];
        foreach ($router->getRoutes()->getRoutes() as $route) {
            // Laravel stores the URIs without the leading slash ("posts"), except for the root route ("/"). So it is
            // trimmed before being added for comparison, otherwise the root route would become "//".
            $uri = '/'.ltrim($route->uri(), '/');
            $name = $route->getName();
            $middleware = $router->gatherRouteMiddleware($route);
            $ignoredBy = $this->findIgnoringPattern($patterns, $uri, $name);

            $mode = match (true) {
                in_array(MultiRegionWrite::class, $middleware, true) => RouteMode::MultiRegionWrite,
                in_array(MultiRegionRead::class, $middleware, true) => RouteMode::MultiRegionRead,
                in_array(NoMultiRegion::class, $middleware, true) => RouteMode::NoMultiRegion,
                $ignoredBy !== null => RouteMode::Ignored,
                default => RouteMode::Missing,
            };

            $routes[] = [
                'methods' => array_values($route->methods()),
                'uri' => $uri,
                'name' => $name,
                'mode' => $mode,
                'ignoredBy' => $mode === RouteMode::Ignored ? $ignoredBy : null,
            ];
        }

        return $routes;
    }

    /**
     * Find the first pattern matching the route. Patterns starting with a slash match the URI, all others the name.
     *
     * @param  list<string>  $patterns
     */
    protected function findIgnoringPattern(array $patterns, string $uri, ?string $name): ?string
    {
        foreach ($patterns as $pattern) {
            if (Str::is($pattern, str_starts_with($pattern, '/') ? $uri : $name ?? '')) {
                return $pattern;
            }
        }

        return null;
    }
}
