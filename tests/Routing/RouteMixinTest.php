<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Tpetry\MultiRegion\Middleware\MultiRegionRead;
use Tpetry\MultiRegion\Middleware\MultiRegionWrite;
use Tpetry\MultiRegion\Middleware\NoMultiRegion;

$macros = [
    'multiRegionRead()' => ['multiRegionRead', MultiRegionRead::class],
    'multiRegionWrite()' => ['multiRegionWrite', MultiRegionWrite::class],
    'noMultiRegion()' => ['noMultiRegion', NoMultiRegion::class],
];

it('adds the middleware to the route', function (string $macro, string $middleware) {
    Route::get('/posts', fn () => 'ok')->name('posts')->middleware('auth')->{$macro}();

    expect(routeMiddleware('posts'))->toBe(['auth', $middleware]);
})->with($macros);

it('keeps middleware added after the macro', function (string $macro, string $middleware) {
    Route::get('/posts', fn () => 'ok')->name('posts')->{$macro}()->middleware('auth');

    expect(routeMiddleware('posts'))->toBe([$middleware, 'auth']);
})->with($macros);

it('replaces the multi-region middleware of the route\'s group', function (string $macro, string $middleware, string $group) {
    Route::middleware(['auth', $group])->group(function () use ($macro) {
        Route::get('/posts', fn () => 'ok')->name('posts')->{$macro}();
    });

    expect(routeMiddleware('posts'))->toBe(['auth', $middleware]);
})->with($macros)->with([
    'read group' => MultiRegionRead::class,
    'write group' => MultiRegionWrite::class,
    'no multi-region group' => NoMultiRegion::class,
]);

it('keeps the group\'s middleware for the other routes', function () {
    Route::middleware(MultiRegionWrite::class)->group(function () {
        Route::get('/posts', fn () => 'ok')->name('posts')->multiRegionRead();
        Route::post('/posts', fn () => 'ok')->name('posts.store');
    });

    expect(routeMiddleware('posts'))->toBe([MultiRegionRead::class])
        ->and(routeMiddleware('posts.store'))->toBe([MultiRegionWrite::class]);
});

it('can be called multiple times with the same macro', function (string $macro, string $middleware) {
    Route::get('/posts', fn () => 'ok')->name('posts')->{$macro}()->{$macro}();

    expect(routeMiddleware('posts'))->toBe([$middleware]);
})->with($macros);

it('fails when combining different macros on the same route', function (string $first, string $second, string $middleware) {
    expect(fn () => Route::get('/posts', fn () => 'ok')->{$first}()->{$second}())->toThrow(
        LogicException::class,
        "The middleware [{$middleware}] can't be applied to the route [posts] as it has been excluded, e.g. by another multi-region macro or withoutMiddleware().",
    );
})->with([
    'read, then write' => ['multiRegionRead', 'multiRegionWrite', MultiRegionWrite::class],
    'write, then read' => ['multiRegionWrite', 'multiRegionRead', MultiRegionRead::class],
    'no multi-region, then write' => ['noMultiRegion', 'multiRegionWrite', MultiRegionWrite::class],
    'write, then no multi-region' => ['multiRegionWrite', 'noMultiRegion', NoMultiRegion::class],
]);

it('fails when the middleware has been excluded manually', function () {
    expect(fn () => Route::get('/posts', fn () => 'ok')->withoutMiddleware(MultiRegionWrite::class)->multiRegionWrite())
        ->toThrow(LogicException::class);
});
