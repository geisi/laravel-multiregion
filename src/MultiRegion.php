<?php

declare(strict_types=1);

namespace Tpetry\MultiRegion;

use Tpetry\MultiRegion\Facades\Manager;

class MultiRegion
{
    /**
     * Determine if this instance is part of the primary region.
     */
    public static function isPrimary(): bool
    {
        return Manager::isPrimary();
    }

    /**
     * Determine if the replica region has received the last write operation of the user.
     */
    public static function hasCaughtUp(): bool
    {
        return Manager::hasCaughtUp();
    }
}
