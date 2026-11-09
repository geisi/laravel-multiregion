<?php

declare(strict_types=1);

namespace Tpetry\MultiRegion\Console;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Helper\TableStyle;
use Tpetry\MultiRegion\Facades\RouteInspector;
use Tpetry\MultiRegion\Routing\RouteMode;

#[AsCommand(name: 'multiregion:routes')]
#[Signature('multiregion:routes')]
#[Description('List all routes with their multi-region mode')]
class RoutesCommand extends Command
{
    public function handle(): int
    {
        $routes = RouteInspector::inspect();
        if ($routes === []) {
            $this->components->info('The application has no routes.');

            return self::SUCCESS;
        }

        $this->table(['Method', 'URL', 'Name', 'Mode'], array_map(function (array $route): array {
            $style = match ($route['mode']) {
                RouteMode::MultiRegionRead => 'fg=cyan',
                RouteMode::MultiRegionWrite => 'fg=magenta',
                RouteMode::NoMultiRegion => 'fg=yellow',
                RouteMode::Ignored => 'fg=gray',
                RouteMode::Missing => 'fg=red;options=bold',
            };

            return array_map(fn (string $value): string => "<{$style}>{$value}</>", [
                implode('|', $route['methods']),
                $route['uri'],
                $route['name'] ?? '',
                match ($route['mode']) {
                    RouteMode::Ignored => "{$route['mode']->value} ({$route['ignoredBy']})",
                    default => $route['mode']->value,
                },
            ]);
        }, $routes), new TableStyle()->setCellHeaderFormat('%s'));

        $missing = count(array_filter($routes, fn (array $route): bool => $route['mode'] === RouteMode::Missing));
        if ($missing > 0) {
            $this->components->error(trans_choice(':count route has no multi-region middleware.|:count routes have no multi-region middleware.', $missing));

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
