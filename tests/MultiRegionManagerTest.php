<?php

declare(strict_types=1);

use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Mockery\MockInterface;
use Tpetry\MultiRegion\Contracts\Driver;
use Tpetry\MultiRegion\Facades\CommitId;
use Tpetry\MultiRegion\MultiRegionManager;
use Tpetry\MultiRegion\Testing\CommitIdState;

beforeEach(function () {
    config([
        'session.driver' => 'cookie',
        'multiregion.verification.milliseconds' => 60_000,
        'multiregion.wait.seconds' => 0,
        'multiregion.wait.timeout' => 500,
    ]);
});

describe('isPrimary()', function () {
    it('fails when the region is not configured', function () {
        (new MultiRegionManager)->isPrimary();
    })->throws(InvalidArgumentException::class, 'Configuration value for key [multiregion.primary] must be a boolean, NULL given.');

    it('is the primary region when configured', function (bool $primary) {
        config(['multiregion.primary' => $primary]);

        expect((new MultiRegionManager)->isPrimary())
            ->toBe($primary);
    })->with([
        'primary' => [true],
        'replica' => [false],
    ]);
});

describe('cleanupOldCommitIds()', function () {
    it('removes a commit id after the verification period', function () {
        $this->freezeTime(function (Carbon $now) {
            session([
                'multiregion.commit_id' => 'commit-973',
                'multiregion.saved_at' => $now->getTimestampMs() - config()->integer('multiregion.verification.milliseconds') - 1,
            ]);

            (new MultiRegionManager)->cleanupOldCommitIds();

            expect(session()->has('multiregion'))
                ->toBeFalse();
        });
    });

    it('keeps the commit id within the verification period', function () {
        $this->freezeTime(function (Carbon $now) {
            session([
                'multiregion.commit_id' => 'commit-369',
                'multiregion.saved_at' => $now->getTimestampMs() - config()->integer('multiregion.verification.milliseconds'),
            ]);

            (new MultiRegionManager)->cleanupOldCommitIds();

            expect(session('multiregion'))
                ->commit_id->toBe('commit-369')
                ->saved_at->toBe($now->getTimestampMs() - config()->integer('multiregion.verification.milliseconds'));
        });
    });
});

describe('recordWrite()', function () {
    it('fails when the session driver is not cookie', function (string $driver) {
        config(['session.driver' => $driver]);

        expect(fn () => (new MultiRegionManager)->recordWrite())
            ->toThrow(RuntimeException::class, 'The multi region middleware requires the "cookie" session driver to share the last commit id with all regions.');
    })->with(['array', 'database', 'redis']);

    it('fails when a transaction is still open', function () {
        DB::beginTransaction();

        expect(fn () => (new MultiRegionManager)->recordWrite())
            ->toThrow(RuntimeException::class, 'The multi region middleware can\'t guarantee read-after-write behavior if a database transaction is still open when trying to get the last commit id.');
    });

    it('stores the last commit id in the session', function () {
        CommitId::fake(CommitIdState::CaughtUp);

        $this->freezeTime(function (Carbon $now) {
            (new MultiRegionManager)->recordWrite();

            expect(session('multiregion'))
                ->commit_id->toBe('commit-4')
                ->saved_at->toBe($now->getTimestampMs());
        });
    });

    it('stores the last commit id in the session (merged with existing commit id)', function () {
        CommitId::fake(CommitIdState::CaughtUp);

        $this->freezeTime(function (Carbon $now) {
            session(['multiregion.commit_id' => 'commit-1', 'multiregion.saved_at' => $now->getTimestampMs() - 1_000]);

            (new MultiRegionManager)->recordWrite();

            expect(session('multiregion'))
                ->commit_id->toBe('commit-1,commit-4')
                ->saved_at->toBe($now->getTimestampMs());
        });
    });
});

describe('hasCaughtUp()', function () {
    it('has caught up without a previous write', function () {
        CommitId::fake(CommitIdState::Lagging);

        expect((new MultiRegionManager)->hasCaughtUp())
            ->toBeTrue();

        CommitId::assertHasCommitIdCalled(null);
    });

    it('has caught up after the verification period', function () {
        CommitId::fake(CommitIdState::Lagging);

        $this->freezeTime(function (Carbon $now) {
            session([
                'multiregion.commit_id' => 'commit-407',
                'multiregion.saved_at' => $now->getTimestampMs() - config()->integer('multiregion.verification.milliseconds') - 1,
            ]);

            expect((new MultiRegionManager)->hasCaughtUp())
                ->toBeTrue();
        });

        CommitId::assertHasCommitIdCalled(null);
    });

    it('has caught up when the data has arrived (inside wait timeout)', function () {
        CommitId::fake(CommitIdState::CaughtUp);

        $this->freezeTime(function (Carbon $now) {
            session(['multiregion.commit_id' => 'commit-302', 'multiregion.saved_at' => $now->getTimestampMs()]);

            expect((new MultiRegionManager)->hasCaughtUp())
                ->toBeTrue();
        });

        CommitId::assertHasCommitIdCalled(['commit-302', config()->integer('multiregion.wait.seconds')]);
    });

    it('has caught up when the data has arrived (outside wait timeout)', function () {
        CommitId::fake(CommitIdState::CaughtUp);
        config(['multiregion.wait.seconds' => 2]);

        $this->freezeTime(function (Carbon $now) {
            session(['multiregion.commit_id' => 'commit-440', 'multiregion.saved_at' => $now->getTimestampMs() - config()->integer('multiregion.wait.timeout')]);

            expect((new MultiRegionManager)->hasCaughtUp())
                ->toBeTrue();
        });

        CommitId::assertHasCommitIdCalled(['commit-440', 0]);
    });

    it('has not caught up when the data has not arrived (inside wait timeout)', function () {
        CommitId::fake(CommitIdState::Lagging);

        $this->freezeTime(function (Carbon $now) {
            session(['multiregion.commit_id' => 'commit-655', 'multiregion.saved_at' => $now->getTimestampMs()]);

            expect((new MultiRegionManager)->hasCaughtUp())
                ->toBeFalse();
        });

        CommitId::assertHasCommitIdCalled(['commit-655', config()->integer('multiregion.wait.seconds')]);
    });

    it('has not caught up when the data has not arrived (outside wait timeout)', function () {
        CommitId::fake(CommitIdState::Lagging);
        config(['multiregion.wait.seconds' => 2]);

        $this->freezeTime(function (Carbon $now) {
            session(['multiregion.commit_id' => 'commit-329', 'multiregion.saved_at' => $now->getTimestampMs() - config()->integer('multiregion.wait.timeout')]);

            expect((new MultiRegionManager)->hasCaughtUp())
                ->toBeFalse();
        });

        CommitId::assertHasCommitIdCalled(['commit-329', 0]);
    });
});

describe('replayAtPrimary()', function () {
    it('passes the current request to the driver and returns its response', function () {
        Request::swap($request = HttpRequest::create('http://replica.test/posts?b=2&a=1'));
        $response = response('replayed');

        $this->mock(Driver::class, function (MockInterface $mock) use ($request, $response) {
            $mock->expects('replayAtPrimary')->with($request)->andReturn($response);
        });

        expect((new MultiRegionManager)->replayAtPrimary())
            ->toBe($response);
    });
});
