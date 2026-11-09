<?php

declare(strict_types=1);

namespace Tpetry\MultiRegion\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tpetry\MultiRegion\Facades\Manager;

class MultiRegionWrite
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        Manager::cleanupOldCommitIds();

        if (! Manager::isPrimary()) {
            return Manager::addServedByHeader(Manager::replayAtPrimary());
        }

        try {
            $response = $next($request);
        } finally {
            Manager::recordWrite();
        }

        return Manager::addServedByHeader($response);
    }
}
