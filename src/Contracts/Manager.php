<?php

declare(strict_types=1);

namespace Tpetry\MultiRegion\Contracts;

use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

interface Manager
{
    /**
     * Determine if this instance is part of the primary region.
     */
    public function isPrimary(): bool;

    /**
     * Remove the commit ID of the last write operation from the session when its verification period has lapsed.
     */
    public function cleanupOldCommitIds(): void;

    /**
     * Store the commit ID of the last write operation in the session for read-after-write consistency.
     *
     * @throws RuntimeException
     */
    public function recordWrite(): void;

    /**
     * Determine if the replica region has received the last write operation of the user.
     */
    public function hasCaughtUp(): bool;

    /**
     * Replay the current request at the primary region.
     */
    public function replayAtPrimary(): Response;

    /**
     * Add the header identifying the region or machine that answered the request.
     */
    public function addServedByHeader(Response $response): Response;
}
