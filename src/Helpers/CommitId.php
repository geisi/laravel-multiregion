<?php

declare(strict_types=1);

namespace Tpetry\MultiRegion\Helpers;

use Illuminate\Database\Connection;

class CommitId
{
    /**
     * Get the ID of the last committed transaction.
     */
    public function getLastCommitId(Connection $connection): string
    {
        return $connection->getLastCommitId();
    }

    /**
     * Calculate the commit ID for a set of commit IDs that includes all of them.
     *
     * @param  string[]  $values
     */
    public function mergeCommitIds(Connection $connection, array $values): string
    {
        return $connection->mergeCommitIds($values);
    }

    /**
     * Determine if the given commit ID has been applied.
     */
    public function hasCommitId(Connection $connection, string $value, int $wait = 0): bool
    {
        return $connection->hasCommitId($value, $wait);
    }
}
