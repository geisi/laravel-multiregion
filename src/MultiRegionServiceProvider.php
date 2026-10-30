<?php

declare(strict_types=1);

namespace Tpetry\MultiRegion;

use Illuminate\Routing\Route;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\ServiceProvider;
use Tpetry\MultiRegion\Helpers\CommitId;
use Tpetry\MultiRegion\Routing\RouteHostRewriter;
use Tpetry\MultiRegion\Routing\RouteMixin;

class MultiRegionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/multiregion.php', 'multiregion');

        $this->app->singleton(CommitId::class);
        $this->app->singleton(MultiRegionManager::class);

        Route::mixin(new RouteMixin);

        $this->callAfterResolving('url', function (UrlGenerator $url): void {
            $url->formatHostUsing($this->app->make(RouteHostRewriter::class)(...));
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
}
