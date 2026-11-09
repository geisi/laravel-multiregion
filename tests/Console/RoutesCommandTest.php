<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Testing\PendingCommand;
use Symfony\Component\Console\Output\BufferedOutput;
use Tpetry\MultiRegion\Facades\RouteInspector;
use Tpetry\MultiRegion\Routing\RouteMode;

it('lists every route colored by its multi-region mode', function () {
    RouteInspector::fake([
        ['methods' => ['GET', 'HEAD'], 'uri' => '/read', 'name' => 'read', 'mode' => RouteMode::MultiRegionRead, 'ignoredBy' => null],
        ['methods' => ['POST'], 'uri' => '/write', 'name' => 'write', 'mode' => RouteMode::MultiRegionWrite, 'ignoredBy' => null],
        ['methods' => ['GET', 'HEAD'], 'uri' => '/no-multiregion', 'name' => 'no-multiregion', 'mode' => RouteMode::NoMultiRegion, 'ignoredBy' => null],
        ['methods' => ['GET', 'HEAD'], 'uri' => '/ignored', 'name' => 'ignored', 'mode' => RouteMode::Ignored, 'ignoredBy' => '/ignored'],
        ['methods' => ['GET', 'HEAD'], 'uri' => '/missing', 'name' => 'missing', 'mode' => RouteMode::Missing, 'ignoredBy' => null],
    ]);

    $output = new BufferedOutput(decorated: true);
    Artisan::call('multiregion:routes', [], $output);

    expect($output->fetch())->toContain(<<<TABLE
        +----------+-----------------+----------------+-------------------------+
        | Method   | URL             | Name           | Mode                    |
        +----------+-----------------+----------------+-------------------------+
        | \e[36mGET|HEAD\e[39m | \e[36m/read\e[39m           | \e[36mread\e[39m           | \e[36muses multiRegionRead()\e[39m  |
        | \e[35mPOST\e[39m     | \e[35m/write\e[39m          | \e[35mwrite\e[39m          | \e[35muses multiRegionWrite()\e[39m |
        | \e[33mGET|HEAD\e[39m | \e[33m/no-multiregion\e[39m | \e[33mno-multiregion\e[39m | \e[33muses noMultiRegion()\e[39m    |
        | \e[90mGET|HEAD\e[39m | \e[90m/ignored\e[39m        | \e[90mignored\e[39m        | \e[90mignored (/ignored)\e[39m      |
        | \e[31;1mGET|HEAD\e[39;22m | \e[31;1m/missing\e[39;22m        | \e[31;1mmissing\e[39;22m        | \e[31;1mmissing\e[39;22m                 |
        +----------+-----------------+----------------+-------------------------+
        TABLE);
});

it('informs when the application has no routes', function () {
    RouteInspector::fake([]);

    /** @var PendingCommand $command */
    $command = $this->artisan('multiregion:routes');
    $command
        ->expectsOutputToContain('The application has no routes.')
        ->assertSuccessful();
});

it('fails when routes have no multi-region middleware', function (int $count, string $message) {
    RouteInspector::fake(array_map(fn (int $i): array => ['methods' => ['GET', 'HEAD'], 'uri' => "/missing-{$i}", 'name' => null, 'mode' => RouteMode::Missing, 'ignoredBy' => null], range(1, $count)));

    /** @var PendingCommand $command */
    $command = $this->artisan('multiregion:routes');
    $command
        ->expectsOutputToContain($message)
        ->assertFailed();
})->with([
    'one route' => [1, '1 route has no multi-region middleware.'],
    'multiple routes' => [2, '2 routes have no multi-region middleware.'],
]);
