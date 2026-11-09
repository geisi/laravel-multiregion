<?php

declare(strict_types=1);

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Tpetry\MultiRegion\Middleware\MultiRegionRead;
use Tpetry\MultiRegion\Middleware\MultiRegionWrite;
use Tpetry\MultiRegion\Routing\RouteInspector;
use Tpetry\MultiRegion\Routing\RouteMode;

/**
 * Inspect the routes and key them by their URI for easier assertions.
 *
 * @param  list<string>  $ignoring
 * @return array<string, array{methods: list<string>, uri: string, name: ?string, mode: RouteMode, ignoredBy: ?string}>
 */
function inspectRoutes(array $ignoring = []): array
{
    app(Router::class)->getRoutes()->refreshNameLookups();

    return array_column(app(RouteInspector::class)->inspect($ignoring), null, 'uri');
}

beforeEach(function () {
    config(['multiregion.routes' => ['presets' => [], 'ignore' => [], 'custom' => []]]);
});

it('returns the methods, uri and name of every route', function () {
    Route::post('/posts', fn () => 'ok')->name('posts.store')->multiRegionWrite();

    expect(inspectRoutes()['/posts'])->toBe(
        ['methods' => ['POST'], 'uri' => '/posts', 'name' => 'posts.store', 'mode' => RouteMode::MultiRegionWrite, 'ignoredBy' => null],
    );
});

it('keeps the leading slash of the root route', function () {
    Route::get('/', fn () => 'ok')->multiRegionRead();

    expect(inspectRoutes())->toHaveKey('/');
});

it('detects the multi-region mode of the route', function (string $macro, RouteMode $mode) {
    Route::get('/route', fn () => 'ok')->{$macro}();

    expect(inspectRoutes()['/route']['mode'])->toBe($mode);
})->with([
    'multiRegionRead()' => ['multiRegionRead', RouteMode::MultiRegionRead],
    'multiRegionWrite()' => ['multiRegionWrite', RouteMode::MultiRegionWrite],
    'noMultiRegion()' => ['noMultiRegion', RouteMode::NoMultiRegion],
]);

it('detects routes without multi-region middleware', function () {
    Route::get('/route', fn () => 'ok');

    expect(inspectRoutes()['/route']['mode'])->toBe(RouteMode::Missing);
});

it('resolves middleware groups, aliases and excluded middleware', function () {
    app(Router::class)->middlewareGroup('multiregion-group', [MultiRegionRead::class]);
    app(Router::class)->aliasMiddleware('multiregion-alias', MultiRegionWrite::class);
    Route::get('/group', fn () => 'ok')->middleware('multiregion-group');
    Route::get('/alias', fn () => 'ok')->middleware('multiregion-alias');
    Route::get('/excluded', fn () => 'ok')->middleware('multiregion-group')->withoutMiddleware(MultiRegionRead::class);

    expect(inspectRoutes())
        ->{'/group'}->mode->toBe(RouteMode::MultiRegionRead)
        ->{'/alias'}->mode->toBe(RouteMode::MultiRegionWrite)
        ->{'/excluded'}->mode->toBe(RouteMode::Missing);
});

describe('ignoring', function () {
    it('ignores routes by their URI', function (string $pattern) {
        Route::get('/storage/a/b', fn () => 'ok');

        expect(inspectRoutes([$pattern])['/storage/a/b'])
            ->mode->toBe(RouteMode::Ignored)
            ->ignoredBy->toBe($pattern);
    })->with([
        'exact' => '/storage/a/b',
        'wildcard' => '/storage/*',
    ]);

    it('ignores routes by their name', function (string $pattern) {
        Route::get('/crm', fn () => 'ok')->name('crm.page.show');

        expect(inspectRoutes([$pattern])['/crm'])
            ->mode->toBe(RouteMode::Ignored)
            ->ignoredBy->toBe($pattern);
    })->with([
        'exact' => 'crm.page.show',
        'wildcard' => 'crm.page.*',
    ]);

    it('ignores routes from the configuration and the additional patterns', function () {
        config(['multiregion.routes.ignore' => ['/storage/*']]);
        Route::get('/storage/a', fn () => 'ok');
        Route::get('/crm', fn () => 'ok')->name('crm.page.show');
        Route::get('/missing', fn () => 'ok');

        expect(inspectRoutes(['crm.page.*']))
            ->{'/storage/a'}->ignoredBy->toBe('/storage/*')
            ->{'/crm'}->ignoredBy->toBe('crm.page.*')
            ->{'/missing'}->mode->toBe(RouteMode::Missing);
    });

    it('reports the first matching pattern', function () {
        config(['multiregion.routes.ignore' => ['/storage/*', '/storage/a']]);
        Route::get('/storage/a', fn () => 'ok');

        expect(inspectRoutes()['/storage/a']['ignoredBy'])->toBe('/storage/*');
    });

    it('prefers the multi-region middleware over an ignore pattern', function () {
        Route::get('/storage/a', fn () => 'ok')->multiRegionRead();

        expect(inspectRoutes(['/storage/*'])['/storage/a'])
            ->mode->toBe(RouteMode::MultiRegionRead)
            ->ignoredBy->toBeNull();
    });
});
