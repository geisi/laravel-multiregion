<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Tpetry\MultiRegion\Facades\MultiRegion;
use Tpetry\MultiRegion\Middleware\NoMultiRegion;
use Tpetry\MultiRegion\Testing\MultiRegionState;

it('passes requests through without multi-region handling', function (MultiRegionState $state) {
    MultiRegion::fake($state);
    Route::post('/up', fn () => 'ok')->middleware(NoMultiRegion::class);

    $this->post('/up')
        ->assertOk()
        ->assertSee('ok');

    MultiRegion::assertOldCommitIdsCleanedUp();
    MultiRegion::assertWriteRecorded(false);
    MultiRegion::assertServedByHeaderAdded();
})->with([
    'primary region' => MultiRegionState::Primary,
    'replica region' => MultiRegionState::ReplicaLagging,
]);
