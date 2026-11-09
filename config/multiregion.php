<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Primary Region
    |--------------------------------------------------------------------------
    |
    | Whether this instance is part of the primary region. Set it to true in
    | the primary region and to false in every replica region, so that this
    | instance forwards requests to the primary region when they can't be
    | handled locally.
    |
    */

    'primary' => env('MULTIREGION_PRIMARY'),

    /*
    |--------------------------------------------------------------------------
    | Driver
    |--------------------------------------------------------------------------
    |
    | The driver defines how a request is repeated at the primary region when
    | a replica region can't handle it. Applications have different
    | capabilities and requirements, so choose the one fitting yours. It may
    | even be different for development than for production, e.g. when no
    | proxy is available locally to replay requests.
    |
    | Use "noop" to disable the multi-region handling, e.g. for local
    | development or unit testing.
    |
    | Supported: "redirect", "header", "fly", "noop"
    |
    */

    'driver' => env('MULTIREGION_DRIVER'),

    'drivers' => [

        /*
        |----------------------------------------------------------------------
        | Redirect Between Primary and Replica
        |----------------------------------------------------------------------
        |
        | This driver will probably be used most in development. There is one
        | URL for the primary region and one for all replica regions. Writes are
        | sent to the primary region's URL, reads to the replica region.
        | Load-balancing should select the fastest one for the user.
        |
        | All requests can still be sent to a replica region although it can't
        | handle them, e.g. a write route or a read before the user's last write
        | has arrived. The middleware detects this and redirects the user to
        | primary region.
        |
        | The write URL is used to redirect requests from a replica region to
        | the primary region. The read URL is used when generating URLs for read
        | routes, so that links generated at the primary region can still be
        | handled by the region with the lowest latency for the user.
        |
        */

        'redirect' => [
            'url' => [
                'read' => env('MULTIREGION_REDIRECT_URL_READ'),
                'write' => env('MULTIREGION_REDIRECT_URL_WRITE'),
            ],
        ],

        /*
        |----------------------------------------------------------------------
        | Response Header
        |----------------------------------------------------------------------
        |
        | All requests are always sent to the application's standard URL, which
        | leads to all regions. When a replica region can't handle a request,
        | e.g. a write route or a read before the user's last write has arrived,
        | it responds with a header containing the absolute URL at which the
        | request should be replayed in the primary region, e.g.:
        |
        |     X-MultiRegion-Replay: https://primary.example.com/posts
        |
        | The replay itself must be implemented in front of the application,
        | e.g. with a Cloudflare Worker that sends the request again to the URL
        | in the header and returns that response to the user.
        |
        */

        'header' => [
            'name' => env('MULTIREGION_HEADER_NAME', 'X-MultiRegion-Replay'),
            'url' => env('MULTIREGION_HEADER_URL'),
        ],

        /*
        |----------------------------------------------------------------------
        | Fly.io Replay
        |----------------------------------------------------------------------
        |
        | Fly.io's proxy natively replays requests in another region when the
        | application responds with a fly-replay header. The target defines
        | where the request is replayed, e.g. "region=ams" for the primary
        | region. See the docs for configuration options:
        | https://docs.fly.io/networking/dynamic-request-routing
        |
        */

        'fly' => [
            'target' => env('MULTIREGION_FLYREPLAY_TARGET'),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Waiting Time
    |--------------------------------------------------------------------------
    |
    | When the written data hasn't arrived in the replica region yet, the user
    | is redirected to the primary region. But a new request to the primary
    | region usually takes longer than the replication lag, so it is often
    | faster to wait a moment for the data to arrive instead.
    |
    | You can specify an optional maximum number of seconds a request waits
    | for the data. As data is replicated usually within a few milliseconds,
    | one second is more than enough. But when the replication is lagging
    | (e.g. network problem) every read request waits the full time before it
    | is redirected. The application then becomes slow for the user, not just
    | once but on every page view.
    |
    | The timeout prevents this by only waiting up to a number of milliseconds
    | after the primary region has written data. With the default of 500ms,
    | only requests that are made within the first 500ms after a write
    | operation wait for the data. Later ones are redirected immediately.
    |
    */

    'wait' => [
        'seconds' => (int) env('MULTIREGION_WAIT_SECONDS', 0),
        'timeout' => (int) env('MULTIREGION_WAIT_TIMEOUT', 500),
    ],

    /*
    |--------------------------------------------------------------------------
    | Verification Duration
    |--------------------------------------------------------------------------
    |
    | After a write operation, a user must see their own changes even though
    | the replica region may not have received them yet. A replica region
    | therefore verifies on every request that the data has arrived. It must
    | do this on every request because when a replica region has multiple
    | database servers, each one of them can have a different replication lag.
    | Marking the data as arrived would only confirm it for one of them, not
    | for the database server the next request is routed to.
    |
    | Verifying costs one tiny additional query on every request in a replica
    | region, which you obviously don't want to run forever. You must
    | therefore define how long after every write operation a replica region
    | should verify the data's existence. Once this duration has passed, the
    | data is expected to have arrived.
    |
    | In rare cases, the data takes very long to arrive, and you don't want to
    | redirect users to the primary region for that long. When the database
    | replication has problems, a replica region may even be unable to ever
    | verify the data. Although it may have arrived. Verifying forever is
    | therefore not recommended. Choose a value well above the worst
    | replication lag you expect.
    |
    */

    'verification' => [
        'milliseconds' => (int) env('MULTIREGION_VERIFICATION_MILLISECONDS', 30_000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Served By
    |--------------------------------------------------------------------------
    |
    | A header added to every response to identify the region or machine in a
    | region that answered the request for debugging and monitoring purposes.
    | The header is only added when a value is set.
    |
    */

    'served_by' => [
        'name' => env('MULTIREGION_SERVEDBY_NAME', 'X-MultiRegion-ServedBy'),
        'value' => env('MULTIREGION_SERVEDBY_VALUE'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    |
    | Every route must have a multi-region setting, which the artisan command
    | and the Pest architecture test verify. But routes registered by the
    | framework or packages can't get one in your route files, so they are
    | configured here instead.
    |
    */

    'routes' => [

        /*
        |----------------------------------------------------------------------
        | Presets
        |----------------------------------------------------------------------
        |
        | Multi-region settings for the routes of popular packages are shipped
        | with this package, enable the ones you use. They only apply to the
        | routes handled by the package's own controllers. When you replace
        | them with your own implementation, you have to configure those
        | routes yourself.
        |
        */

        'presets' => [
            'laravel/fortify' => false,
            'laravel/passkeys' => false,
        ],

        /*
        |----------------------------------------------------------------------
        | Ignore
        |----------------------------------------------------------------------
        |
        | Routes not needing any multi-region setting, e.g. routes only used
        | for local development. They are matched by their URL (starting
        | with a slash, e.g. "/up") or by their name (e.g. "horizon.*").
        | Both may contain wildcards.
        |
        */

        'ignore' => [
            '/_boost/*', // laravel/boost
            '/_debugbar/*', // fruitcake/laravel-debugbar
            '/_inertia/*', // inertiajs/inertia-laravel
            '/laravel-login-link-login', // spatie/laravel-login-link
            '/storage/*',
            '/up',
        ],

        /*
        |----------------------------------------------------------------------
        | Custom
        |----------------------------------------------------------------------
        |
        | Routes of packages without a preset that use the database can't be
        | ignored, they need a multi-region setting to work correctly. Set
        | the mode "read", "write" or "none" for them, the route is selected
        | by its name (e.g. "login.store") or URL (starting with a slash,
        | e.g. "/login"). Entries not matching any route are skipped.
        |
        | The method and action ensure the mode is applied to the correct
        | route when routes change, e.g. after a package update or reusing a
        | route name. The method is the HTTP method (e.g. "POST"), and the
        | action is the controller and method, e.g.
        | "Laravel\Fortify\Http\Controllers\PasswordController@update", or, for
        | closure routes, the file defining the closure, e.g.
        | "vendor/laravel/boost/src/BoostServiceProvider.php". Wildcards can
        | be used to simplify them, e.g. "Laravel\Fortify\Http\Controllers\*".
        |
        */

        'custom' => [
            // [
            //     'route' => 'teams.show',
            //     'action' => 'Laravel\Jetstream\Http\Controllers\Livewire\TeamController@show',
            //     'method' => 'GET',
            //     'mode' => 'read',
            // ],
        ],

    ],

];
