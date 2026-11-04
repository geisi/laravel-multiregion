<?php

declare(strict_types=1);

namespace Tpetry\MultiRegion\Helpers;

use Illuminate\Http\Request;
use Illuminate\Support\Uri;

class TargetUrl
{
    public static function buildUrl(Request $request, string $destination): string
    {
        // The raw query string is set on the underlying League URI because Uri::withQuery() only accepts an array which
        // is not the exact query string passed to this request. Minor differences due to parsing the query string into
        // an array and serializing it again could e.g. break signed URLs or have different behavior with duplicate
        // values etc.
        return Uri::of($destination)
            ->withPath($request->getBaseUrl().$request->getPathInfo())
            ->getUri()
            ->withQuery($request->server->getString('QUERY_STRING') ?: null)
            ->toString();
    }
}
