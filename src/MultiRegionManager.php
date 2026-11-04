<?php

declare(strict_types=1);

namespace Tpetry\MultiRegion;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Tpetry\MultiRegion\Contracts\Driver;
use Tpetry\MultiRegion\Facades\CommitId;

class MultiRegionManager
{
    /**
     * Determine if this instance is part of the primary region.
     */
    public function isPrimary(): bool
    {
        return config()->boolean('multiregion.primary');
    }

    /**
     * Remove the commit ID of the last write operation from the session when its verification period has lapsed.
     */
    public function cleanupOldCommitIds(): void
    {
        if (blank(Session::get('multiregion'))) {
            return;
        }

        if (now()->getTimestampMs() - (int) (Session::get('multiregion.saved_at') ?? 0) > config()->integer('multiregion.verification.milliseconds')) {
            Session::forget('multiregion');
        }
    }

    /**
     * Store the commit ID of the last write operation in the session for read-after-write consistency.
     *
     * @throws RuntimeException
     */
    public function recordWrite(): void
    {
        // The commit ID has to be readable in every region. Only the cookie driver stores the session on the client and
        // is therefore available everywhere, while the other drivers store it in a region-local backend. Having the
        // necessary cross-region communication would be a lot of work without any other session driver.
        if (Session::getDefaultDriver() !== 'cookie') {
            throw new RuntimeException('The multi region middleware requires the "cookie" session driver to share the last commit id with all regions.');
        }

        // The commit ID within an open transaction points to data written before the transaction. That is probably
        // never what the developer intended. As a transaction shouldn't even be open when the request finished, we
        // found a possible bug in the code. Throw an exception to alert the developer that read-after-write is possibly
        // violated if we continue.
        if (DB::connection()->transactionLevel() > 0) {
            throw new RuntimeException('The multi region middleware can\'t guarantee read-after-write behavior if a database transaction is still open when trying to get the last commit id.');
        }

        $lastCommitId = Session::get('multiregion.commit_id');
        $commitId = match ($lastCommitId) {
            null => CommitId::getLastCommitId(DB::connection()),
            default => CommitId::mergeCommitIds(DB::connection(), [$lastCommitId, CommitId::getLastCommitId(DB::connection())]),
        };

        Session::put('multiregion', [
            'commit_id' => $commitId,
            'saved_at' => now()->getTimestampMs(),
        ]);
    }

    /**
     * Determine if the replica region has received the last write operation of the user.
     */
    public function hasCaughtUp(): bool
    {
        $commitAge = now()->getTimestampMs() - (int) Session::get('multiregion.saved_at');
        $commitId = Session::get('multiregion.commit_id');

        if (blank($commitId) || $commitAge > config()->integer('multiregion.verification.milliseconds')) {
            return true;
        }

        $wait = match ($commitAge < config()->integer('multiregion.wait.timeout')) {
            true => config()->integer('multiregion.wait.seconds'),
            false => 0,
        };

        return CommitId::hasCommitId(DB::connection(), $commitId, $wait);
    }

    /**
     * Replay the current request at the primary region.
     */
    public function replayAtPrimary(): Response
    {
        return app(Driver::class)->replayAtPrimary(Request::instance());
    }
}
