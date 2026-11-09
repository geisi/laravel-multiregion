<?php

declare(strict_types=1);

namespace Tpetry\MultiRegion\Routing;

use Closure;
use Illuminate\Routing\PendingResourceRegistration;
use Illuminate\Routing\PendingSingletonResourceRegistration;
use Illuminate\Support\Arr;
use Tpetry\MultiRegion\Middleware\MultiRegionRead;
use Tpetry\MultiRegion\Middleware\MultiRegionWrite;
use Tpetry\MultiRegion\Middleware\NoMultiRegion;

/**
 * @mixin PendingResourceRegistration
 * @mixin PendingSingletonResourceRegistration
 */
class ResourceMixin
{
    public function multiRegion(): Closure
    {
        return function (): PendingResourceRegistration|PendingSingletonResourceRegistration {
            /** @var PendingResourceRegistration|PendingSingletonResourceRegistration $registration */
            $registration = $this; // @phpstan-ignore varTag.nativeType
            $modes = [
                'index' => MultiRegionRead::class,
                'create' => MultiRegionRead::class,
                'store' => MultiRegionWrite::class,
                'show' => MultiRegionRead::class,
                'edit' => MultiRegionRead::class,
                'update' => MultiRegionWrite::class,
                'destroy' => MultiRegionWrite::class,
            ];

            foreach ($modes as $method => $middleware) {
                // The framework replaces the middleware of a method on every call, so the ones added before (e.g. with
                // middlewareFor()) are merged.
                $others = array_diff([MultiRegionRead::class, MultiRegionWrite::class, NoMultiRegion::class], [$middleware]);
                $registration->middlewareFor($method, [...Arr::wrap($registration->options['middleware_for'][$method] ?? []), $middleware]);
                $registration->withoutMiddlewareFor($method, [...Arr::wrap($registration->options['excluded_middleware_for'][$method] ?? []), ...$others]);
            }

            return $registration;
        };
    }
}
