<?php

declare(strict_types=1);

namespace Tpetry\MultiRegion\Routing;

use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use InvalidArgumentException;
use Tpetry\MultiRegion\Middleware\MultiRegionRead;
use Tpetry\MultiRegion\Middleware\MultiRegionWrite;

/**
 * Laravel generates URLs for the current instance. In a multi-region setup that works but is inefficient: a link to a
 * write route generated in a replica region points to the replica, which must then redirect the request to the write
 * URL. This rewriter instead points URLs to the endpoint that should handle them, based on the route's middleware:
 * - `MultiRegionWrite`: The write URL, as only the primary region can handle writes.
 * - `MultiRegionRead`: The read URL. With a suitable configuration, it can also lead to the primary region.
 */
class RouteHostRewriter
{
    public function __construct(
        protected Router $router,
    ) {}

    public function __invoke(string $root, mixed $route): string
    {
        // URLs generated with url() have no route and can't be assigned to an endpoint. Routes with a manually set
        // domain are kept as they are to not break the application's routing.
        if (! $route instanceof Route || $route->getDomain() !== null) {
            return $root;
        }

        $middleware = $this->router->gatherRouteMiddleware($route);

        return match (true) {
            in_array(MultiRegionWrite::class, $middleware, true) => $this->endpointUrl('write', $route),
            in_array(MultiRegionRead::class, $middleware, true) => $this->endpointUrl('read', $route),
            default => $root,
        };
    }

    protected function endpointUrl(string $endpoint, Route $route): string
    {
        $url = config()->get("multiregion.url.{$endpoint}");
        if (! is_string($url) || $url === '') {
            // Laravel adds HEAD to every GET route, which is only noise in the error message. Routes only registered
            // for HEAD keep it.
            $methods = $route->methods();
            if (in_array('GET', $methods, true)) {
                $methods = array_diff($methods, ['HEAD']);
            }
            $description = implode('|', $methods).' /'.ltrim($route->uri(), '/');

            throw new InvalidArgumentException("The config option multiregion.url.{$endpoint} is not set, but is needed to generate the URL for the route '{$description}'.");
        }

        return rtrim($url, '/');
    }
}
