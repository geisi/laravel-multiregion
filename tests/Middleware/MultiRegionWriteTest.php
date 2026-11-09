<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Tpetry\MultiRegion\Facades\Manager;
use Tpetry\MultiRegion\Middleware\MultiRegionWrite;
use Tpetry\MultiRegion\Testing\ManagerState;

beforeEach(function () {
    Route::post('/posts', fn () => 'ok')->middleware(MultiRegionWrite::class);
});

describe('primary region', function () {
    it('passes requests through and records the write', function () {
        Manager::fake(ManagerState::Primary);

        $this->post('/posts')
            ->assertOk()
            ->assertSee('ok');

        Manager::assertReplayedAtPrimary(false);
        Manager::assertOldCommitIdsCleanedUp();
        Manager::assertWriteRecorded(true);
        Manager::assertServedByHeaderAdded();
    });

    it('records the write for failed requests', function () {
        Manager::fake(ManagerState::Primary);
        Route::post('/failing', fn () => throw new RuntimeException('failed'))->middleware(MultiRegionWrite::class);

        $this->post('/failing')
            ->assertServerError();

        Manager::assertReplayedAtPrimary(false);
        Manager::assertOldCommitIdsCleanedUp();
        Manager::assertWriteRecorded(true);
        Manager::assertServedByHeaderAdded();
    });
});

describe('replica region', function () {
    it('replays the request at the primary region', function () {
        Manager::fake(ManagerState::ReplicaCaughtUp, $response = response('replayed'));

        expect($this->post('/posts')->baseResponse)
            ->toBe($response);

        Manager::assertReplayedAtPrimary(true);
        Manager::assertOldCommitIdsCleanedUp();
        Manager::assertWriteRecorded(false);
        Manager::assertServedByHeaderAdded();
    });
});
