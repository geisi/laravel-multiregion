<?php

declare(strict_types=1);

namespace Tpetry\MultiRegion\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tpetry\MultiRegion\Facades\Manager;

/**
 * Marks a route as not being part of the multi-region system. It doesn't do anything. It only exists for documentation
 * and verification purposes to indicate that the multi-region setting on a route was not forgotten but intentionally
 * not used. Examples are e.g. when no database is used or on health checks.
 */
class NoMultiRegion
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // This is strictly speaking not needed, but it is nevertheless a good idea to clean up the session's value and
        // therefore the cookie if the information is no longer needed. The cookie size is limited...
        Manager::cleanupOldCommitIds();

        return Manager::addServedByHeader($next($request));
    }
}
