<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tpetry\MultiRegion\Drivers\Redirect;
use Tpetry\MultiRegion\Middleware\MultiRegionRead;
use Tpetry\MultiRegion\Middleware\MultiRegionWrite;

describe('replayAtPrimary', function () {
    it('redirects to the url at the primary region', function () {
        $response = (new Redirect('https://replica.test', 'https://primary.test'))
            ->replayAtPrimary(Request::create('http://replica.test/posts?b=2&a=1'));

        expect($response)
            ->getStatusCode()->toBe(307)
            ->getTargetUrl()->toBe('https://primary.test/posts?b=2&a=1');
    });

    it('fails when the write url is not configured', function () {
        expect(fn () => (new Redirect('https://replica.test', null))->replayAtPrimary(Request::create('http://replica.test/posts')))
            ->toThrow(InvalidArgumentException::class, 'The config option multiregion.drivers.redirect.url.write is not set, but is needed to redirect to the primary region.');
    });
});

describe('formatRouteHost', function () {
    it('uses the write URL for the write endpoint', function () {
        $route = Route::post('/posts', fn () => 'ok')->middleware(MultiRegionWrite::class);

        expect((new Redirect('https://read.test', 'https://write.test'))->formatRouteHost('http://current.test', $route))
            ->toBe('https://write.test');
    });

    it('uses the write URL for routes with the read and write middleware', function () {
        $route = Route::post('/posts', fn () => 'ok')->middleware([MultiRegionRead::class, MultiRegionWrite::class]);

        expect((new Redirect('https://read.test', 'https://write.test'))->formatRouteHost('http://current.test', $route))
            ->toBe('https://write.test');
    });

    it('uses the read URL for the read endpoint', function () {
        $route = Route::get('/posts', fn () => 'ok')->middleware(MultiRegionRead::class);

        expect((new Redirect('https://read.test', 'https://write.test'))->formatRouteHost('http://current.test', $route))
            ->toBe('https://read.test');
    });

    it('keeps the current host for non-multiregion routes', function () {
        $route = Route::get('/posts', fn () => 'ok');

        expect((new Redirect('https://read.test', 'https://write.test'))->formatRouteHost('http://current.test', $route))
            ->toBe('http://current.test');
    });

    it('keeps the current host when the multi-region middleware is excluded', function () {
        Route::middleware(MultiRegionRead::class)->group(function () use (&$route) {
            $route = Route::get('/posts', fn () => 'ok')->withoutMiddleware(MultiRegionRead::class);
        });

        expect((new Redirect('https://read.test', 'https://write.test'))->formatRouteHost('http://current.test', $route))
            ->toBe('http://current.test');
    });

    it('keeps a manually set domain', function () {
        $route = Route::domain('tenant.test')->middleware(MultiRegionWrite::class)->post('/posts', fn () => 'ok');

        expect((new Redirect('https://read.test', 'https://write.test'))->formatRouteHost('http://current.test', $route))
            ->toBe('http://current.test');
    });

    it('keeps the current host for URLs without a route', function () {
        expect((new Redirect('https://read.test', 'https://write.test'))->formatRouteHost('http://current.test', null))
            ->toBe('http://current.test');
    });

    it('removes a trailing slash of the URL', function () {
        $route = Route::post('/posts', fn () => 'ok')->middleware(MultiRegionWrite::class);

        expect((new Redirect('https://read.test/', 'https://write.test/'))->formatRouteHost('http://current.test', $route))
            ->toBe('https://write.test');
    });

    it('fails when the write URL is not configured', function (?string $value) {
        $route = Route::post('/posts', fn () => 'ok')->middleware(MultiRegionWrite::class);

        expect(fn () => (new Redirect('https://read.test', $value))->formatRouteHost('http://current.test', $route))->toThrow(
            InvalidArgumentException::class,
            "The config option multiregion.drivers.redirect.url.write is not set, but is needed to generate the URL for the route 'POST /posts'.",
        );
    })->with([
        'null' => null,
        'empty string' => '',
    ]);

    it('fails when the read URL is not configured', function (?string $value) {
        $route = Route::get('/posts', fn () => 'ok')->middleware(MultiRegionRead::class);

        expect(fn () => (new Redirect($value, 'https://write.test'))->formatRouteHost('http://current.test', $route))->toThrow(
            InvalidArgumentException::class,
            "The config option multiregion.drivers.redirect.url.read is not set, but is needed to generate the URL for the route 'GET /posts'.",
        );
    })->with([
        'null' => null,
        'empty string' => '',
    ]);
});
