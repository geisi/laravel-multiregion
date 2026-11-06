<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Tpetry\MultiRegion\Facades\MultiRegion;
use Tpetry\MultiRegion\Middleware\MultiRegionWrite;
use Tpetry\MultiRegion\Testing\MultiRegionState;

beforeEach(function () {
    Route::post('/posts', fn () => 'ok')->middleware(MultiRegionWrite::class);
});

describe('primary region', function () {
    it('passes requests through and records the write', function () {
        MultiRegion::fake(MultiRegionState::Primary);

        $this->post('/posts')
            ->assertOk()
            ->assertSee('ok');

        MultiRegion::assertReplayedAtPrimary(false);
        MultiRegion::assertOldCommitIdsCleanedUp();
        MultiRegion::assertWriteRecorded(true);
        MultiRegion::assertServedByHeaderAdded();
    });

    it('records the write for failed requests', function () {
        MultiRegion::fake(MultiRegionState::Primary);
        Route::post('/failing', fn () => throw new RuntimeException('failed'))->middleware(MultiRegionWrite::class);

        $this->post('/failing')
            ->assertServerError();

        MultiRegion::assertReplayedAtPrimary(false);
        MultiRegion::assertOldCommitIdsCleanedUp();
        MultiRegion::assertWriteRecorded(true);
        MultiRegion::assertServedByHeaderAdded();
    });
});

describe('replica region', function () {
    it('replays the request at the primary region', function () {
        MultiRegion::fake(MultiRegionState::ReplicaCaughtUp, $response = response('replayed'));

        expect($this->post('/posts')->baseResponse)
            ->toBe($response);

        MultiRegion::assertReplayedAtPrimary(true);
        MultiRegion::assertOldCommitIdsCleanedUp();
        MultiRegion::assertWriteRecorded(false);
        MultiRegion::assertServedByHeaderAdded();
    });
});
