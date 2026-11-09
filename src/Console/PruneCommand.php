<?php

declare(strict_types=1);

namespace Tpetry\MultiRegion\Console;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'multiregion:prune')]
#[Signature('multiregion:prune
    {connection : The primary database connection storing the measurements}
    {--days=7 : The number of days to keep the measurements of the replication lag}')]
#[Description('Prune the old measurements of the replication lag')]
class PruneCommand extends Command
{
    public function handle(): int
    {
        /** @var class-string<Factory<Model>> $factory */
        $factory = config()->string('multiregion.monitoring.model.factory');
        $count = $factory::new()->modelName()::on((string) $this->argument('connection'))
            ->where('created_at', '<', now()->subDays((int) $this->option('days')))
            ->delete();

        $this->components->info(trans_choice(':count measurement has been pruned.|:count measurements have been pruned.', $count));

        return self::SUCCESS;
    }
}
