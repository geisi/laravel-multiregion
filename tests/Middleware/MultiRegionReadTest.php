<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Tpetry\MultiRegion\Facades\Manager;
use Tpetry\MultiRegion\Middleware\MultiRegionRead;
use Tpetry\MultiRegion\Testing\ManagerState;

beforeEach(function () {
    Route::get('/posts', fn () => 'ok')->middleware(MultiRegionRead::class);
});

describe('primary region', function () {
    it('passes requests through', function () {
        Manager::fake(ManagerState::Primary);

        $this->get('/posts')
            ->assertOk()
            ->assertSee('ok');

        Manager::assertReplayedAtPrimary(false);
        Manager::assertOldCommitIdsCleanedUp();
        Manager::assertServedByHeaderAdded();
    });
});

describe('replica region', function () {
    it('passes requests through when the data has arrived', function () {
        Manager::fake(ManagerState::ReplicaCaughtUp);

        $this->get('/posts')
            ->assertOk()
            ->assertSee('ok');

        Manager::assertReplayedAtPrimary(false);
        Manager::assertOldCommitIdsCleanedUp();
        Manager::assertServedByHeaderAdded();
    });

    it('replays the request at the primary region when the data has not arrived', function () {
        Manager::fake(ManagerState::ReplicaLagging, $response = response('replayed'));

        expect($this->get('/posts')->baseResponse)
            ->toBe($response);

        Manager::assertReplayedAtPrimary(true);
        Manager::assertOldCommitIdsCleanedUp();
        Manager::assertServedByHeaderAdded();
    });
});
