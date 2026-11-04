<?php

declare(strict_types=1);

namespace Tpetry\MultiRegion\Drivers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tpetry\MultiRegion\Contracts\Driver;
use Tpetry\MultiRegion\Helpers\TargetUrl;

class Header implements Driver
{
    public function __construct(
        protected string $name,
        protected string $url,
    ) {}

    public function replayAtPrimary(Request $request): Response
    {
        return response('', Response::HTTP_NO_CONTENT, [$this->name => TargetUrl::buildUrl($request, $this->url)]);
    }
}
