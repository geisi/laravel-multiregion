<?php

declare(strict_types=1);

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Tpetry\MultiRegion\Middleware\MultiRegionRead;
use Tpetry\MultiRegion\Middleware\MultiRegionWrite;

beforeEach(function () {
    URL::useOrigin('http://current.test');
    config([
        'multiregion.url.read' => 'https://read.test',
        'multiregion.url.write' => 'https://write.test',
    ]);

    Route::get('/read', fn () => 'ok')->name('multiregion-read')->middleware(MultiRegionRead::class);
    Route::post('/write', fn () => 'ok')->name('multiregion-write')->middleware(MultiRegionWrite::class);
    Route::get('/no-multiregion', fn () => 'ok')->name('no-multiregion');
    app(Router::class)->getRoutes()->refreshNameLookups();
});

it('uses the write URL for the write endpoint', function () {
    expect(route('multiregion-write'))->toBe('https://write.test/write');
});

it('uses the write URL for routes with the read and write middleware', function () {
    Route::name('target')->middleware([MultiRegionRead::class, MultiRegionWrite::class])->post('/posts', fn () => 'ok');

    expect(route('target'))->toBe('https://write.test/posts');
});

it('uses the read URL for the read endpoint', function () {
    expect(route('multiregion-read'))->toBe('https://read.test/read');
});

it('keeps the current host for routes non-multiregion routes', function () {
    expect(route('no-multiregion'))->toBe('http://current.test/no-multiregion');
});

it('keeps the current host when the multi-region middleware is excluded', function () {
    Route::middleware(MultiRegionRead::class)->group(function () {
        Route::name('target')->withoutMiddleware(MultiRegionRead::class)->get('/posts', fn () => 'ok');
    });

    expect(route('target'))->toBe('http://current.test/posts');
});

it('keeps a manually set domain', function () {
    Route::domain('tenant.test')->middleware(MultiRegionWrite::class)->name('target')->post('/posts', fn () => 'ok');

    expect(route('target'))->toBe('http://tenant.test:'.request()->getPort().'/posts');
});

it('does not change URLs without a route', function () {
    expect(url('/write'))->toBe('http://current.test/write');
});

it('fails when the write URL is not configured', function (mixed $value) {
    config(['multiregion.url.write' => $value]);

    expect(fn () => route('multiregion-write'))->toThrow(
        InvalidArgumentException::class,
        "The config option multiregion.url.write is not set, but is needed to generate the URL for the route 'POST /write'.",
    );
})->with([
    'null' => null,
    'empty string' => '',
]);

it('fails when the read URL is not configured', function (mixed $value) {
    config(['multiregion.url.read' => $value]);

    expect(fn () => route('multiregion-read'))->toThrow(
        InvalidArgumentException::class,
        "The config option multiregion.url.read is not set, but is needed to generate the URL for the route 'GET /read'.",
    );
})->with([
    'null' => null,
    'empty string' => '',
]);
