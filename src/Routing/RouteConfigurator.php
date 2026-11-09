<?php

declare(strict_types=1);

namespace Tpetry\MultiRegion\Routing;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Str;
use InvalidArgumentException;
use ReflectionFunction;

/**
 * @phpstan-type RouteEntry array{
 *     route: string,
 *     method: string,
 *     action: string,
 *     mode: 'read'|'write'|'none',
 * }
 */
class RouteConfigurator
{
    /**
     * The route macros applying the multi-region middleware for every mode.
     */
    protected const array MACROS = [
        'read' => 'multiRegionRead',
        'write' => 'multiRegionWrite',
        'none' => 'noMultiRegion',
    ];

    public function __construct(
        protected Application $app,
    ) {}

    /**
     * Get the configuration file's multi-region routes: The enabled presets followed by the custom routes.
     *
     * @return list<RouteEntry>
     */
    public function configured(): array
    {
        $config = $this->app->make('config');

        $presets = [];
        foreach (array_keys(array_filter($config->array('multiregion.routes.presets', []))) as $package) {
            $file = __DIR__.'/../Presets/'.preg_replace('/[^a-z0-9_-]/', '-', strtolower((string) $package)).'.php';
            if (! is_file($file)) {
                continue;
            }

            $presets = [...$presets, ...require $file];
        }

        $entries = [];
        foreach ([...$presets, ...$config->array('multiregion.routes.custom', [])] as $entry) {
            $route = is_array($entry) ? ($entry['route'] ?? null) : null;
            if (! is_string($route) || blank($route)) {
                throw new InvalidArgumentException('Every multi-region route needs a route name (e.g. login) or URL (e.g. /login).');
            }

            /** @var array<string, mixed> $entry */
            $method = $entry['method'] ?? null;
            if (! is_string($method) || ! in_array($method, Router::$verbs, true)) {
                throw new InvalidArgumentException("The route [{$route}] needs a method (e.g. POST) to ensure the multi-region mode is applied to the correct route.");
            }

            $action = $entry['action'] ?? null;
            if (! is_string($action) || blank($action)) {
                throw new InvalidArgumentException("The route [{$route}] needs an action to ensure the multi-region mode is applied to the correct route.");
            }

            $mode = $entry['mode'] ?? null;
            if (! is_string($mode) || ! isset(self::MACROS[$mode])) {
                throw new InvalidArgumentException("The multi-region mode of the route [{$route}] must be one of: ".implode(', ', array_keys(self::MACROS)).'.');
            }

            /** @var 'read'|'write'|'none' $mode */
            $entries[] = compact('route', 'method', 'action', 'mode');
        }

        return $entries;
    }

    /**
     * Apply the multi-region mode of the configured routes (presets and custom routes) to the routes registered by the
     * framework or packages. Entries not matching any route are skipped, as e.g. the package may not be installed. When
     * multiple entries match the same route, the last one wins, so custom routes replace the presets.
     */
    public function apply(): void
    {
        $router = $this->app->make(Router::class);

        // Names assigned after a route has been added to the collection (e.g. Route::get(...)->name(...)) are only
        // known after refreshing the lookup table.
        $router->getRoutes()->refreshNameLookups();

        $modes = [];
        foreach ($this->configured() as $entry) {
            $routes = match (str_starts_with($entry['route'], '/')) {
                true => array_filter($router->getRoutes()->getRoutes(), fn (Route $route): bool => ltrim($route->uri(), '/') === ltrim($entry['route'], '/')),
                false => array_filter([$router->getRoutes()->getByName($entry['route'])]),
            };

            foreach ($routes as $route) {
                if (! in_array($entry['method'], $route->methods(), true)) {
                    continue;
                }
                if (! Str::is($entry['action'], $this->actionOf($route))) {
                    continue;
                }

                // A route is deduplicated by its methods, URL and domain, e.g. "POST /login @ app.example.com".
                $modes[implode('|', $route->methods()).' /'.ltrim($route->uri(), '/').' @ '.$route->getDomain()] = [$route, self::MACROS[$entry['mode']]];
            }
        }

        foreach ($modes as [$route, $macro]) {
            $route->{$macro}();
        }
    }

    /**
     * Get the action handling the route: the controller and method (e.g. "App\Http\Controllers\PostController@index")
     * or the file defining the closure relative to the application (e.g. "routes/web.php").
     */
    protected function actionOf(Route $route): string
    {
        $uses = $route->getAction('uses');
        if (! $uses instanceof Closure) {
            // Invokable controllers are stored with the method when registered as [Controller::class, '__invoke'] but
            // without when registered as Controller::class. The method is always added for consistent patterns.
            $action = $route->getActionName();

            return str_contains($action, '@') ? $action : "{$action}@__invoke";
        }

        $file = str_replace('\\', '/', (string) new ReflectionFunction($uses)->getFileName());
        $base = str_replace('\\', '/', $this->app->basePath()).'/';

        return str_starts_with($file, $base) ? substr($file, strlen($base)) : $file;
    }
}
