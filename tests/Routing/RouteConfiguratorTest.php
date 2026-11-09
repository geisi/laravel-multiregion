<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Tpetry\MultiRegion\Middleware\MultiRegionRead;
use Tpetry\MultiRegion\Middleware\MultiRegionWrite;
use Tpetry\MultiRegion\Middleware\NoMultiRegion;
use Tpetry\MultiRegion\Routing\RouteConfigurator;

beforeEach(function () {
    config(['multiregion.routes' => ['presets' => [], 'ignore' => [], 'custom' => []]]);
});

describe('applies the mode to the route', function () {
    it('selected by its name', function (string $mode, string $middleware) {
        Route::post('/login', $action = 'Vendor\Auth\LoginController@store')->name('login.store')->middleware('guest');
        config(['multiregion.routes.custom' => [['route' => 'login.store', 'method' => 'POST', 'action' => $action, 'mode' => $mode]]]);
        app(RouteConfigurator::class)->apply();

        expect(routeMiddleware('login.store'))->toBe(['guest', $middleware]);
    })->with([
        'read' => ['read', MultiRegionRead::class],
        'write' => ['write', MultiRegionWrite::class],
        'none' => ['none', NoMultiRegion::class],
    ]);

    it('selected by its URL', function () {
        Route::get('/login', 'Vendor\Auth\LoginController@create')->name('login');
        Route::post('/login', 'Vendor\Auth\LoginController@store')->name('login.store');
        config(['multiregion.routes.custom' => [
            ['route' => '/login', 'method' => 'GET', 'action' => 'Vendor\Auth\*', 'mode' => 'read'],
            ['route' => '/login', 'method' => 'POST', 'action' => 'Vendor\Auth\*', 'mode' => 'write'],
        ]]);
        app(RouteConfigurator::class)->apply();

        expect(routeMiddleware('login'))->toBe([MultiRegionRead::class])
            ->and(routeMiddleware('login.store'))->toBe([MultiRegionWrite::class]);
    });

    it('matches GET routes also responding to HEAD', function () {
        Route::get('/posts', 'Vendor\PostController@index')->name('posts.index');
        config(['multiregion.routes.custom' => [['route' => 'posts.index', 'method' => 'GET', 'action' => 'Vendor\*', 'mode' => 'read']]]);
        app(RouteConfigurator::class)->apply();

        expect(routeMiddleware('posts.index'))->toBe([MultiRegionRead::class]);
    });

    it('matches the controller action', function (string $action) {
        Route::post('/login', 'Vendor\Auth\LoginController@store')->name('login.store');
        config(['multiregion.routes.custom' => [['route' => 'login.store', 'method' => 'POST', 'action' => $action, 'mode' => 'write']]]);
        app(RouteConfigurator::class)->apply();

        expect(routeMiddleware('login.store'))->toBe([MultiRegionWrite::class]);
    })->with([
        'exact' => 'Vendor\Auth\LoginController@store',
        'namespace wildcard' => 'Vendor\Auth\*',
        'method wildcard' => 'Vendor\*@store',
    ]);

    it('matches invokable controllers with the __invoke method', function (string|array $uses) {
        Route::get('/verify', $uses)->name('verify');
        config(['multiregion.routes.custom' => [['route' => 'verify', 'method' => 'GET', 'action' => 'Illuminate\Foundation\Http\HealthCheck@__invoke', 'mode' => 'write']]]);
        app(RouteConfigurator::class)->apply();

        expect(routeMiddleware('verify'))->toBe([MultiRegionWrite::class]);
    })->with([
        'class' => 'Illuminate\Foundation\Http\HealthCheck',
        'class and method' => [['Illuminate\Foundation\Http\HealthCheck', '__invoke']],
    ]);

    it('matches closure routes by the file defining the closure relative to the application', function () {
        app()->setBasePath(dirname(__DIR__, 2));
        Route::get('/health', fn () => 'ok')->name('health');
        config(['multiregion.routes.custom' => [['route' => 'health', 'method' => 'GET', 'action' => 'tests/Routing/RouteConfiguratorTest.php', 'mode' => 'none']]]);
        app(RouteConfigurator::class)->apply();

        expect(routeMiddleware('health'))->toBe([NoMultiRegion::class]);
    });
});

describe('skips entries when nothing matching is found', function () {
    it('skips unknown routes', function (string $route) {
        Route::post('/login', 'Vendor\Auth\LoginController@store')->name('login.store');
        config(['multiregion.routes.custom' => [['route' => $route, 'method' => 'POST', 'action' => 'Vendor\*', 'mode' => 'write']]]);
        app(RouteConfigurator::class)->apply();

        expect(routeMiddleware('login.store'))->toBe([]);
    })->with([
        'name' => 'logout',
        'URL' => '/logout',
    ]);

    it('skips routes with another method', function (string $route) {
        Route::post('/login', 'Vendor\Auth\LoginController@store')->name('login.store');
        config(['multiregion.routes.custom' => [['route' => $route, 'method' => 'GET', 'action' => 'Vendor\*', 'mode' => 'write']]]);
        app(RouteConfigurator::class)->apply();

        expect(routeMiddleware('login.store'))->toBe([]);
    })->with([
        'by name' => 'login.store',
        'by URL' => '/login',
    ]);

    it('skips routes with another controller action', function (string $action) {
        Route::post('/login', 'Vendor\Auth\LoginController@store')->name('login.store');
        config(['multiregion.routes.custom' => [['route' => 'login.store', 'method' => 'POST', 'action' => $action, 'mode' => 'write']]]);
        app(RouteConfigurator::class)->apply();

        expect(routeMiddleware('login.store'))->toBe([]);
    })->with([
        'other controller' => 'Vendor\Auth\LogoutController@store',
        'other method' => 'Vendor\Auth\LoginController@create',
        'other namespace' => 'App\Http\Controllers\*',
    ]);

    it('skips closure routes defined in another file', function () {
        app()->setBasePath(dirname(__DIR__, 2));
        Route::get('/health', fn () => 'ok')->name('health');
        config(['multiregion.routes.custom' => [['route' => 'health', 'method' => 'GET', 'action' => 'routes/*', 'mode' => 'none']]]);
        app(RouteConfigurator::class)->apply();

        expect(routeMiddleware('health'))->toBe([]);
    });
});

describe('fails for an invalid configuration', function () {
    it('fails without a route', function (array $route) {
        config(['multiregion.routes.custom' => [[...$route, 'method' => 'GET', 'action' => 'Vendor\*', 'mode' => 'read']]]);

        expect(fn () => app(RouteConfigurator::class)->apply())->toThrow(
            InvalidArgumentException::class,
            'Every multi-region route needs a route name (e.g. login) or URL (e.g. /login).',
        );
    })->with([
        'missing' => [[]],
        'empty' => [['route' => '']],
    ]);

    it('fails without a valid method', function (array $method) {
        config(['multiregion.routes.custom' => [['route' => 'login', ...$method, 'action' => 'Vendor\*', 'mode' => 'read']]]);

        expect(fn () => app(RouteConfigurator::class)->apply())->toThrow(
            InvalidArgumentException::class,
            'The route [login] needs a method (e.g. POST) to ensure the multi-region mode is applied to the correct route.',
        );
    })->with([
        'missing' => [[]],
        'list of methods' => [['method' => ['GET', 'POST']]],
        'not a method' => [['method' => 'GET|POST']],
        'unknown method' => [['method' => 'GTE']],
        'lowercase' => [['method' => 'get']],
    ]);

    it('fails without an action', function () {
        config(['multiregion.routes.custom' => [['route' => 'login', 'method' => 'GET', 'mode' => 'read']]]);

        expect(fn () => app(RouteConfigurator::class)->apply())->toThrow(
            InvalidArgumentException::class,
            'The route [login] needs an action to ensure the multi-region mode is applied to the correct route.',
        );
    });

    it('fails for an unknown mode', function () {
        config(['multiregion.routes.custom' => [['route' => 'login', 'method' => 'GET', 'action' => 'Vendor\*', 'mode' => 'wirte']]]);

        expect(fn () => app(RouteConfigurator::class)->apply())->toThrow(
            InvalidArgumentException::class,
            'The multi-region mode of the route [login] must be one of: read, write, none.',
        );
    });
});
