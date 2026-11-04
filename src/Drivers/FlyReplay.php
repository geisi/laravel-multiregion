<?php

declare(strict_types=1);

namespace Tpetry\MultiRegion\Drivers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tpetry\MultiRegion\Contracts\Driver;

class FlyReplay implements Driver
{
    public function __construct(
        protected string $target,
    ) {}

    public function replayAtPrimary(Request $request): Response
    {
        return response('', Response::HTTP_NO_CONTENT, ['fly-replay' => $this->target]);
    }
}
