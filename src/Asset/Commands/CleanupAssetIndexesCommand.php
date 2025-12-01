<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Commands;

use craft\console\Application;
use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Container\Attributes\Give;

final class CleanupAssetIndexesCommand extends Command
{
    use CraftCommand;

    protected $signature = 'craft:index-assets:cleanup';

    protected $description = 'Removes all CLI indexing sessions.';

    protected $aliases = ['index-assets/cleanup'];

    public function handle(#[Give('Craft')] Application $craft): void
    {
        $total = $craft->getAssetIndexer()->removeCliIndexingSessions();

        $this->components->success("Removed {$total} CLI indexing ".Str::plural('session', $total));
    }
}
