<?php

declare(strict_types=1);

namespace Tpetry\MultiRegion\Managers;

use LogicException;
use Symfony\Component\HttpFoundation\Response;
use Tpetry\MultiRegion\Contracts\Manager;

class Noop implements Manager
{
    public function isPrimary(): bool
    {
        return true;
    }

    public function cleanupOldCommitIds(): void {}

    public function recordWrite(): void {}

    public function hasCaughtUp(): bool
    {
        return true;
    }

    public function replayAtPrimary(): Response
    {
        throw new LogicException('The noop multi-region driver never replays requests.');
    }

    public function addServedByHeader(Response $response): Response
    {
        $name = config('multiregion.served_by.name');
        $value = config('multiregion.served_by.value');

        if (filled($name) && filled($value)) {
            $response->headers->set($name, $value);
        }

        return $response;
    }
}
