<?php

declare(strict_types=1);

use Illuminate\Routing\PendingResourceRegistration;
use Illuminate\Support\Facades\Route;
use Tpetry\MultiRegion\Middleware\MultiRegionRead;
use Tpetry\MultiRegion\Middleware\MultiRegionWrite;
use Tpetry\MultiRegion\Middleware\NoMultiRegion;

it('applies the read and write middleware to the resource routes', function () {
    Route::resource('posts', 'PostController')->middleware('auth')->multiRegion();

    expect(routeMiddleware('posts.index'))->toBe(['auth', MultiRegionRead::class])
        ->and(routeMiddleware('posts.create'))->toBe(['auth', MultiRegionRead::class])
        ->and(routeMiddleware('posts.show'))->toBe(['auth', MultiRegionRead::class])
        ->and(routeMiddleware('posts.edit'))->toBe(['auth', MultiRegionRead::class])
        ->and(routeMiddleware('posts.store'))->toBe(['auth', MultiRegionWrite::class])
        ->and(routeMiddleware('posts.update'))->toBe(['auth', MultiRegionWrite::class])
        ->and(routeMiddleware('posts.destroy'))->toBe(['auth', MultiRegionWrite::class]);
});

it('applies the read and write middleware to the singleton resource routes', function () {
    Route::singleton('profile', 'PostController')->creatable()->multiRegion();

    expect(routeMiddleware('profile.create'))->toBe([MultiRegionRead::class])
        ->and(routeMiddleware('profile.show'))->toBe([MultiRegionRead::class])
        ->and(routeMiddleware('profile.edit'))->toBe([MultiRegionRead::class])
        ->and(routeMiddleware('profile.store'))->toBe([MultiRegionWrite::class])
        ->and(routeMiddleware('profile.update'))->toBe([MultiRegionWrite::class])
        ->and(routeMiddleware('profile.destroy'))->toBe([MultiRegionWrite::class]);
});

it('applies the read and write middleware to the api resource routes', function () {
    Route::apiResource('posts', 'PostController')->multiRegion();

    expect(routeMiddleware('posts.index'))->toBe([MultiRegionRead::class])
        ->and(routeMiddleware('posts.show'))->toBe([MultiRegionRead::class])
        ->and(routeMiddleware('posts.store'))->toBe([MultiRegionWrite::class])
        ->and(routeMiddleware('posts.update'))->toBe([MultiRegionWrite::class])
        ->and(routeMiddleware('posts.destroy'))->toBe([MultiRegionWrite::class]);
});

it('applies the read and write middleware to the api singleton resource routes', function () {
    Route::apiSingleton('profile', 'PostController')->creatable()->multiRegion();

    expect(routeMiddleware('profile.show'))->toBe([MultiRegionRead::class])
        ->and(routeMiddleware('profile.store'))->toBe([MultiRegionWrite::class])
        ->and(routeMiddleware('profile.update'))->toBe([MultiRegionWrite::class])
        ->and(routeMiddleware('profile.destroy'))->toBe([MultiRegionWrite::class]);
});

it('applies the middleware to partial resources', function (Closure $partial) {
    $partial(Route::resource('posts', 'PostController'));

    expect(routeMiddleware('posts.show'))->toBe([MultiRegionRead::class])
        ->and(routeMiddleware('posts.update'))->toBe([MultiRegionWrite::class])
        ->and(Route::has(['posts.index', 'posts.create', 'posts.store', 'posts.edit', 'posts.destroy']))->toBeFalse();
})->with([
    'only() before the macro' => fn (PendingResourceRegistration $resource) => $resource->only(['show', 'update'])->multiRegion(),
    'only() after the macro' => fn (PendingResourceRegistration $resource) => $resource->multiRegion()->only(['show', 'update']),
    'except() before the macro' => fn (PendingResourceRegistration $resource) => $resource->except(['index', 'create', 'store', 'edit', 'destroy'])->multiRegion(),
    'except() after the macro' => fn (PendingResourceRegistration $resource) => $resource->multiRegion()->except(['index', 'create', 'store', 'edit', 'destroy']),
]);

it('keeps middleware added before and after the macro', function () {
    Route::resource('posts', 'PostController')
        ->middlewareFor('store', 'can:create')
        ->multiRegion()
        ->middleware('auth');

    expect(routeMiddleware('posts.index'))->toBe([MultiRegionRead::class, 'auth'])
        ->and(routeMiddleware('posts.store'))->toBe(['can:create', MultiRegionWrite::class, 'auth']);
});

it('replaces the multi-region middleware of the resource\'s group', function (string $group) {
    Route::middleware(['auth', $group])->group(function () {
        Route::resource('posts', 'PostController')->multiRegion();
    });

    expect(routeMiddleware('posts.index'))->toBe(['auth', MultiRegionRead::class])
        ->and(routeMiddleware('posts.store'))->toBe(['auth', MultiRegionWrite::class]);
})->with([
    'read group' => MultiRegionRead::class,
    'write group' => MultiRegionWrite::class,
    'no multi-region group' => NoMultiRegion::class,
]);
