<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Mockery\MockInterface;
use Tpetry\MultiRegion\Contracts\Driver;
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

        MultiRegion::assertOldCommitIdsCleanedUp();
    });
});

describe('replica region', function () {
    it('passes requests through when the data has arrived', function () {
        MultiRegion::fake(MultiRegionState::ReplicaCaughtUp);

        $this->get('/posts')
            ->assertOk()
            ->assertSee('ok');

        MultiRegion::assertOldCommitIdsCleanedUp();
    });

    it('replays the request at the primary region when the data has not arrived', function () {
        MultiRegion::fake(MultiRegionState::ReplicaLagging);
        $response = response('replayed');
        $this->mock(Driver::class, function (MockInterface $mock) use ($response) {
            $mock->expects('replayAtPrimary')->andReturn($response);
        });

        expect($this->get('/posts')->baseResponse)
            ->toBe($response);

        MultiRegion::assertOldCommitIdsCleanedUp();
    });
});
