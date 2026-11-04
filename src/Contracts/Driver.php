<?php

declare(strict_types=1);

namespace Tpetry\MultiRegion\Contracts;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

interface Driver
{
    /**
     * Create the response that makes the request being repeated at the primary region.
     */
    public function replayAtPrimary(Request $request): Response;
}
