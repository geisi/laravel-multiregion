<?php

declare(strict_types=1);

namespace Tpetry\MultiRegion\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Tpetry\MultiRegion\MultiRegion;

class HealthController
{
    public function __invoke(): JsonResponse
    {
        $primary = DB::connection(config()->string('multiregion.monitoring.health.primary_connection'));
        $replica = DB::connection();

        $delay = MultiRegion::measureLag($primary, $replica, config()->integer('multiregion.monitoring.health.wait_seconds'));
        $healthy = $delay !== null && $delay < config()->integer('multiregion.monitoring.health.healthy_milliseconds');

        return response()->json([
            'healthy' => $healthy,
            'delay' => $delay,
        ], $healthy ? 200 : 503);
    }
}
