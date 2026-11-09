<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Tpetry\MultiRegion\Facades\CommitId;
use Tpetry\MultiRegion\Middleware\NoMultiRegion;
use Tpetry\MultiRegion\Monitoring\Lag;
use Tpetry\MultiRegion\MultiRegionServiceProvider;
use Tpetry\MultiRegion\Testing\CommitIdState;

/**
 * Boot the package again, so the health check route is registered with the current configuration.
 */
function registerHealthRoute(): void
{
    (new MultiRegionServiceProvider(app()))->boot();
    Route::getRoutes()->refreshNameLookups();
}

beforeEach(function () {
    (require dirname(__DIR__, 2).'/database/migrations/0000_00_00_000000_create_multiregion_lags_table.php')->up();
    config([
        'multiregion.monitoring.health.primary_connection' => 'testing',
        'multiregion.monitoring.health.wait_seconds' => 3,
        'multiregion.monitoring.health.url' => '/health/multiregion',
        'multiregion.monitoring.health.healthy_milliseconds' => 1_000,
    ]);
});

it('is healthy when the lag is below the healthy time', function () {
    $fake = CommitId::fake(CommitIdState::CaughtUp);
    registerHealthRoute();

    $this->getJson('/health/multiregion')
        ->assertOk()
        ->assertJson(['healthy' => true]);

    $fake->assertHasCommitIdCalled(['commit-4', 3]);
    expect(Lag::sole()->delay)->toBeInt();
});

it('stores the configured attributes with the measurement', function () {
    CommitId::fake(CommitIdState::CaughtUp);
    config(['multiregion.monitoring.model.attributes' => ['region' => 'eu-central-1']]);
    registerHealthRoute();

    $this->getJson('/health/multiregion')->assertOk();

    expect(Lag::sole()->attributes)->toBe(['region' => 'eu-central-1']);
});

it('is unhealthy when the lag is not below the healthy time', function () {
    CommitId::fake(CommitIdState::CaughtUp);
    config(['multiregion.monitoring.health.healthy_milliseconds' => 0]);
    registerHealthRoute();

    $this->getJson('/health/multiregion')
        ->assertServiceUnavailable()
        ->assertJson(['healthy' => false]);
});

it('is unhealthy when the entry has not been replicated within the waiting time', function () {
    CommitId::fake(CommitIdState::Lagging);
    registerHealthRoute();

    $this->getJson('/health/multiregion')
        ->assertServiceUnavailable()
        ->assertExactJson(['healthy' => false, 'delay' => null]);

    expect(Lag::sole()->delay)->toBeNull();
});

it('uses no multi-region middleware', function () {
    registerHealthRoute();

    expect(routeMiddleware('multiregion.health'))->toBe([NoMultiRegion::class]);
});

it('registers no route without a health check URL', function () {
    config(['multiregion.monitoring.health.url' => null]);
    registerHealthRoute();

    expect(Route::has('multiregion.health'))->toBeFalse();
});
