<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Tpetry\MultiRegion\Facades\MultiRegion;
use Tpetry\MultiRegion\Middleware\MultiRegionRead;
use Tpetry\MultiRegion\Testing\MultiRegionState;

beforeEach(function () {
    Route::get('/posts', fn () => 'ok')->middleware(MultiRegionRead::class);
});

describe('primary region', function () {
    it('passes requests through', function () {
        MultiRegion::fake(MultiRegionState::Primary);

        $this->get('/posts')
            ->assertOk()
            ->assertSee('ok');

        MultiRegion::assertReplayedAtPrimary(false);
        MultiRegion::assertOldCommitIdsCleanedUp();
        MultiRegion::assertServedByHeaderAdded();
    });
});

describe('replica region', function () {
    it('passes requests through when the data has arrived', function () {
        MultiRegion::fake(MultiRegionState::ReplicaCaughtUp);

        $this->get('/posts')
            ->assertOk()
            ->assertSee('ok');

        MultiRegion::assertReplayedAtPrimary(false);
        MultiRegion::assertOldCommitIdsCleanedUp();
        MultiRegion::assertServedByHeaderAdded();
    });

    it('replays the request at the primary region when the data has not arrived', function () {
        MultiRegion::fake(MultiRegionState::ReplicaLagging, $response = response('replayed'));

        expect($this->get('/posts')->baseResponse)
            ->toBe($response);

        MultiRegion::assertReplayedAtPrimary(true);
        MultiRegion::assertOldCommitIdsCleanedUp();
        MultiRegion::assertServedByHeaderAdded();
    });
});
