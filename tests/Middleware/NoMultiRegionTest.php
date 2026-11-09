<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Tpetry\MultiRegion\Facades\Manager;
use Tpetry\MultiRegion\Middleware\NoMultiRegion;
use Tpetry\MultiRegion\Testing\ManagerState;

it('passes requests through without multi-region handling', function (ManagerState $state) {
    Manager::fake($state);
    Route::post('/up', fn () => 'ok')->middleware(NoMultiRegion::class);

    $this->post('/up')
        ->assertOk()
        ->assertSee('ok');

    Manager::assertReplayedAtPrimary(false);
    Manager::assertOldCommitIdsCleanedUp();
    Manager::assertWriteRecorded(false);
    Manager::assertServedByHeaderAdded();
})->with([
    'primary region' => ManagerState::Primary,
    'replica region' => ManagerState::ReplicaLagging,
]);
