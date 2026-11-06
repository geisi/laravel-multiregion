<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\AssertionFailedError;
use Tpetry\MultiRegion\Facades\RouteInspector;
use Tpetry\MultiRegion\Routing\RouteMode;

/**
 * A real route for the architecture test, all other tests use a fake RouteInspector.
 */
beforeEach(function () {
    Route::get('/read', fn () => 'ok')->multiRegionRead();
});

it('passes when no route is missing a multi-region middleware', function () {
    RouteInspector::fake([
        ['methods' => ['GET', 'HEAD'], 'uri' => '/read', 'name' => null, 'mode' => RouteMode::MultiRegionRead, 'ignoredBy' => null],
        ['methods' => ['GET', 'HEAD'], 'uri' => '/write', 'name' => null, 'mode' => RouteMode::MultiRegionWrite, 'ignoredBy' => null],
        ['methods' => ['GET', 'HEAD'], 'uri' => '/no-multiregion', 'name' => null, 'mode' => RouteMode::NoMultiRegion, 'ignoredBy' => null],
        ['methods' => ['GET', 'HEAD'], 'uri' => '/ignored', 'name' => null, 'mode' => RouteMode::Ignored, 'ignoredBy' => '/ignored'],
    ]);

    $this->expectRoutes()->toUseMultiRegionMiddleware();
});

it('fails for a route without a multi-region middleware', function (?string $name, string $message) {
    RouteInspector::fake([
        ['methods' => ['GET', 'HEAD'], 'uri' => '/read', 'name' => null, 'mode' => RouteMode::MultiRegionRead, 'ignoredBy' => null],
        ['methods' => ['GET', 'HEAD'], 'uri' => '/missing', 'name' => $name, 'mode' => RouteMode::Missing, 'ignoredBy' => null],
    ]);

    expect(fn () => $this->expectRoutes()->toUseMultiRegionMiddleware())
        ->toThrow(AssertionFailedError::class, $message);
})->with([
    'with name' => ['missing', 'Expecting /missing (missing) to use multi-region middleware.'],
    'without name' => [null, 'Expecting /missing to use multi-region middleware.'],
]);

it('passes the ignoring() patterns to the inspection', function () {
    $fake = RouteInspector::fake([]);

    $this->expectRoutes()->toUseMultiRegionMiddleware()->ignoring('/storage/*')->ignoring(['crm.page.*', 'horizon.*']);

    $fake->assertInspectedWith(['/storage/*', 'crm.page.*', 'horizon.*']);
});

arch('works as an architecture test')
    ->expectRoutes()
    ->toUseMultiRegionMiddleware();
