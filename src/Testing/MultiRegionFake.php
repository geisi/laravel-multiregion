<?php

declare(strict_types=1);

namespace Tpetry\MultiRegion\Testing;

use Illuminate\Support\Testing\Fakes\Fake;
use PHPUnit\Framework\Assert as PHPUnit;
use Symfony\Component\HttpFoundation\Response;
use Tpetry\MultiRegion\Contracts\Manager;

class MultiRegionFake implements Fake, Manager
{
    protected bool $calledRecordWrite = false;

    protected bool $calledCleanupOldCommitIds = false;

    protected bool $calledAddServedByHeader = false;

    protected bool $calledReplayAtPrimary = false;

    protected bool $isPrimary;

    protected bool $hasCaughtUp;

    public function __construct(
        MultiRegionState $state,
        protected Response $replayResponse,
    ) {
        [$this->isPrimary, $this->hasCaughtUp] = match ($state) {
            MultiRegionState::Primary => [true, true],
            MultiRegionState::ReplicaCaughtUp => [false, true],
            MultiRegionState::ReplicaLagging => [false, false],
        };
    }

    public function isPrimary(): bool
    {
        return $this->isPrimary;
    }

    public function cleanupOldCommitIds(): void
    {
        $this->calledCleanupOldCommitIds = true;
    }

    public function recordWrite(): void
    {
        $this->calledRecordWrite = true;
    }

    public function hasCaughtUp(): bool
    {
        return $this->hasCaughtUp;
    }

    public function replayAtPrimary(): Response
    {
        $this->calledReplayAtPrimary = true;

        return $this->replayResponse;
    }

    public function addServedByHeader(Response $response): Response
    {
        $this->calledAddServedByHeader = true;

        return $response;
    }

    public function assertWriteRecorded(bool $value): void
    {
        PHPUnit::assertSame($value, $this->calledRecordWrite, match ($value) {
            true => 'No write operation was recorded.',
            false => 'A write operation was recorded unexpectedly.',
        });
    }

    public function assertReplayedAtPrimary(bool $value): void
    {
        PHPUnit::assertSame($value, $this->calledReplayAtPrimary, match ($value) {
            true => 'The request was not replayed at the primary region.',
            false => 'The request was replayed at the primary region unexpectedly.',
        });
    }

    public function assertOldCommitIdsCleanedUp(): void
    {
        PHPUnit::assertTrue($this->calledCleanupOldCommitIds, 'The old commit IDs were not cleaned up.');
    }

    public function assertServedByHeaderAdded(): void
    {
        PHPUnit::assertTrue($this->calledAddServedByHeader, 'The served by header was not added.');
    }
}
