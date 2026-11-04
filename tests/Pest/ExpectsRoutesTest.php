<?php

declare(strict_types=1);

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\AssertionFailedError;

beforeEach(function () {
    Route::get('/read', fn () => 'ok')->name('multiregion-read')->multiRegionRead();
    Route::post('/write', fn () => 'ok')->name('multiregion-write')->multiRegionWrite();
    Route::get('/no-multiregion', fn () => 'ok')->name('no-multiregion')->noMultiRegion();
    app(Router::class)->getRoutes()->refreshNameLookups();
});

it('passes when every route uses a multi-region middleware', function () {
    $this->expectRoutes()->toUseMultiRegionMiddleware();
});

describe('fails when one route is declared without a multi-region middleware', function () {
    it('shows the route\'s name', function () {
        Route::get('/missing', fn () => 'ok')->name('missing');
        app(Router::class)->getRoutes()->refreshNameLookups();

        expect(fn () => $this->expectRoutes()->toUseMultiRegionMiddleware())
            ->toThrow(AssertionFailedError::class, 'Expecting /missing (missing) to use multi-region middleware.');
    });

    it('shows only the URI for a route without a name', function () {
        Route::get('/missing', fn () => 'ok');

        expect(fn () => $this->expectRoutes()->toUseMultiRegionMiddleware())
            ->toThrow(AssertionFailedError::class, 'Expecting /missing to use multi-region middleware.');
    });
});

describe('ignoring()', function () {
    it('ignores routes by their URI', function (string $pattern) {
        Route::get('/storage/a/b', fn () => 'ok');

        $this->expectRoutes()->toUseMultiRegionMiddleware()->ignoring($pattern);
    })->with([
        'exact' => '/storage/a/b',
        'wildcard' => '/storage/*',
    ]);

    it('ignores routes by their name', function (string $pattern) {
        Route::name('crm.page.show')->get('/crm', fn () => 'ok');

        $this->expectRoutes()->toUseMultiRegionMiddleware()->ignoring($pattern);
    })->with([
        'exact' => 'crm.page.show',
        'wildcard' => 'crm.page.*',
    ]);

    it('combines multiple ignoring() calls', function () {
        Route::get('/storage/a', fn () => 'ok');
        Route::name('crm.page.show')->get('/crm', fn () => 'ok');

        $this->expectRoutes()->toUseMultiRegionMiddleware()->ignoring('/storage/*')->ignoring(['crm.page.*']);
    });
});

arch('works as an architecture test')
    ->expectRoutes()
    ->toUseMultiRegionMiddleware()
    ->ignoring(['/up']);
