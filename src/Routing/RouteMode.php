<?php

declare(strict_types=1);

namespace Tpetry\MultiRegion\Routing;

enum RouteMode: string
{
    /**
     * The route uses the MultiRegionRead middleware.
     */
    case MultiRegionRead = 'uses multiRegionRead()';

    /**
     * The route uses the MultiRegionWrite middleware.
     */
    case MultiRegionWrite = 'uses multiRegionWrite()';

    /**
     * The route uses the NoMultiRegion middleware.
     */
    case NoMultiRegion = 'uses noMultiRegion()';

    /**
     * The route has no multi-region middleware but is ignored by the configuration.
     */
    case Ignored = 'ignored';

    /**
     * The route has no multi-region middleware, it is missing!
     */
    case Missing = 'missing';
}
