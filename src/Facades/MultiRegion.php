<?php

declare(strict_types=1);

namespace Tpetry\MultiRegion\Facades;

use Illuminate\Support\Facades\Facade;
use Symfony\Component\HttpFoundation\Response;
use Tpetry\MultiRegion\Contracts\Manager;
use Tpetry\MultiRegion\Testing\MultiRegionFake;
use Tpetry\MultiRegion\Testing\MultiRegionState;

/**
 * @method static bool isPrimary()
 * @method static void cleanupOldCommitIds()
 * @method static bool hasCaughtUp()
 * @method static void recordWrite()
 * @method static \Symfony\Component\HttpFoundation\Response replayAtPrimary()
 * @method static \Symfony\Component\HttpFoundation\Response addServedByHeader(\Symfony\Component\HttpFoundation\Response $response)
 * @method static void assertWriteRecorded(bool $value)
 * @method static void assertReplayedAtPrimary(bool $value)
 * @method static void assertOldCommitIdsCleanedUp()
 * @method static void assertServedByHeaderAdded()
 *
 * @see Manager
 * @see MultiRegionFake
 */
class MultiRegion extends Facade
{
    public static function fake(MultiRegionState $state, ?Response $replayResponse = null): MultiRegionFake
    {
        return tap(new MultiRegionFake($state, $replayResponse ?? new Response), function (MultiRegionFake $fake) {
            static::swap($fake);
        });
    }

    protected static function getFacadeAccessor(): string
    {
        return Manager::class;
    }
}
