<?php

declare(strict_types=1);

namespace Tpetry\MultiRegion\Drivers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;
use Tpetry\MultiRegion\Contracts\Driver;
use Tpetry\MultiRegion\Contracts\FormatsRouteHost;
use Tpetry\MultiRegion\Helpers\TargetUrl;
use Tpetry\MultiRegion\Middleware\MultiRegionRead;
use Tpetry\MultiRegion\Middleware\MultiRegionWrite;

/**
 * Redirects requests to the primary region's write URL.
 *
 * Laravel generates URLs for the current instance. In a multi-region setup that works but is inefficient: a link to a
 * write route generated in a replica region points to the replica, which must then redirect the request to the write
 * URL. This driver instead points URLs to the endpoint that should handle them, based on the route's middleware:
 * - `MultiRegionWrite`: The write URL, as only the primary region can handle writes.
 * - `MultiRegionRead`: The read URL. With a suitable configuration, it can also lead to the primary region.
 */
class Redirect implements Driver, FormatsRouteHost
{
    public function __construct(
        protected ?string $readUrl,
        protected ?string $writeUrl,
    ) {}

    public function replayAtPrimary(Request $request): RedirectResponse
    {
        if (blank($this->writeUrl)) {
            throw new InvalidArgumentException('The config option multiregion.drivers.redirect.url.write is not set, but is needed to redirect to the primary region.');
        }

        return redirect()->away(TargetUrl::buildUrl($request, $this->writeUrl), Response::HTTP_TEMPORARY_REDIRECT);
    }

    public function formatRouteHost(string $root, ?Route $route): string
    {
        // URLs generated with url() have no route and can't be assigned to an endpoint. Routes with a manually set
        // domain are kept as they are to not break the application's routing.
        if ($route === null || $route->getDomain() !== null) {
            return $root;
        }

        $middleware = RouteFacade::gatherRouteMiddleware($route);

        return match (true) {
            in_array(MultiRegionWrite::class, $middleware, true) => $this->endpointUrl('write', $this->writeUrl, $route),
            in_array(MultiRegionRead::class, $middleware, true) => $this->endpointUrl('read', $this->readUrl, $route),
            default => $root,
        };
    }

    protected function endpointUrl(string $endpoint, ?string $url, Route $route): string
    {
        if (blank($url)) {
            // Laravel adds HEAD to every GET route, which is only noise in the error message. Routes only registered
            // for HEAD keep it.
            $methods = $route->methods();
            if (in_array('GET', $methods, true)) {
                $methods = array_diff($methods, ['HEAD']);
            }
            $description = implode('|', $methods).' /'.ltrim($route->uri(), '/');

            throw new InvalidArgumentException("The config option multiregion.drivers.redirect.url.{$endpoint} is not set, but is needed to generate the URL for the route '{$description}'.");
        }

        return rtrim($url, '/');
    }
}
