<?php

declare(strict_types=1);

namespace Tpetry\MultiRegion\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tpetry\MultiRegion\Facades\Manager;

class MultiRegionRead
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        Manager::cleanupOldCommitIds();

        if (Manager::isPrimary() || Manager::hasCaughtUp()) {
            return Manager::addServedByHeader($next($request));
        }

        return Manager::addServedByHeader(Manager::replayAtPrimary());
    }
}
