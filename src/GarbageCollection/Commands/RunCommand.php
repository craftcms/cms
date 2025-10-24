<?php

declare(strict_types=1);

namespace CraftCms\Cms\GarbageCollection\Commands;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\GarbageCollection\GarbageCollection;
use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;

final class RunCommand extends Command
{
    use CraftCommand;

    protected $signature = 'craft:gc:run
        {--deleteAllTrashed : Whether all soft-deleted items should be deleted, rather than just the ones that were deleted long enough ago to be ready for hard-deletion per the `softDeleteDuration` config setting.}
        {--silent : Whether to suppress all output.}
    ';

    protected $description = 'Allows you to manage garbage collection.';

    protected $aliases = ['gc/run', 'garbage-collection:run'];

    public function handle(GarbageCollection $garbageCollection): void
    {
        $garbageCollection->deleteAllTrashed = $this->option('deleteAllTrashed') || ($this->input->isInteractive() && confirm('Delete all trashed items?'));
        $garbageCollection->silent = $this->option('silent');

        $this->components->info('Running garbage collection ...');

        $garbageCollection->run(force: true);

        $this->components->success('Finished running garbage collection.');
    }
}
