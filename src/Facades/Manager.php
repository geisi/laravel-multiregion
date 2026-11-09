<?php

declare(strict_types=1);

namespace Tpetry\MultiRegion\Facades;

use Illuminate\Support\Facades\Facade;
use Symfony\Component\HttpFoundation\Response;
use Tpetry\MultiRegion\Contracts\Manager as ManagerContract;
use Tpetry\MultiRegion\Testing\ManagerFake;
use Tpetry\MultiRegion\Testing\ManagerState;

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
 * @see ManagerContract
 * @see ManagerFake
 */
class Manager extends Facade
{
    public static function fake(ManagerState $state, ?Response $replayResponse = null): ManagerFake
    {
        return tap(new ManagerFake($state, $replayResponse ?? new Response), function (ManagerFake $fake) {
            static::swap($fake);
        });
    }

    protected static function getFacadeAccessor(): string
    {
        return ManagerContract::class;
    }
}
