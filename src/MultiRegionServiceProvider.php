<?php

declare(strict_types=1);

namespace Tpetry\MultiRegion;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Routing\Route;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use LogicException;
use Tpetry\MultiRegion\Contracts\Driver;
use Tpetry\MultiRegion\Contracts\FormatsRouteHost;
use Tpetry\MultiRegion\Contracts\Manager;
use Tpetry\MultiRegion\Drivers\FlyReplay;
use Tpetry\MultiRegion\Drivers\Header;
use Tpetry\MultiRegion\Drivers\Redirect;
use Tpetry\MultiRegion\Helpers\CommitId;
use Tpetry\MultiRegion\Managers\Noop;
use Tpetry\MultiRegion\Managers\Standard;
use Tpetry\MultiRegion\Routing\RouteMixin;

class MultiRegionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/multiregion.php', 'multiregion');

        $this->app->singleton(CommitId::class);
        $this->app->singleton(Manager::class, fn (Application $app) => match ($app->make('config')->get('multiregion.driver') === 'noop') {
            true => new Noop,
            false => new Standard,
        });
        $this->app->singleton(Driver::class, $this->buildDriver(...));

        Route::mixin(new RouteMixin);

        $this->callAfterResolving('url', function (UrlGenerator $url): void {
            $url->formatHostUsing($this->formatHost(...));
        });
    }

    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/multiregion.php' => config_path('multiregion.php'),
        ], ['multiregion-config']);
    }

    protected function buildDriver(Application $app): Driver
    {
        $config = $app->make('config');

        return match ($driver = $config->string('multiregion.driver')) {
            'redirect' => new Redirect(
                readUrl: $config->get('multiregion.drivers.redirect.url.read'),
                writeUrl: $config->get('multiregion.drivers.redirect.url.write'),
            ),
            'header' => new Header(
                name: $config->string('multiregion.drivers.header.name'),
                url: $config->string('multiregion.drivers.header.url'),
            ),
            'fly' => new FlyReplay(
                target: $config->string('multiregion.drivers.fly.target'),
            ),
            'noop' => throw new LogicException('The noop multi-region driver has no driver implementation, as multi-region handling is disabled.'),
            default => throw new InvalidArgumentException("The multi-region driver [{$driver}] is not supported."),
        };
    }

    protected function formatHost(string $root, ?Route $route): string
    {
        if ($this->app->make('config')->get('multiregion.driver') === 'noop') {
            return $root;
        }

        $driver = $this->app->make(Driver::class);

        return match (true) {
            $driver instanceof FormatsRouteHost => $driver->formatRouteHost($root, $route),
            default => $root,
        };
    }
}
