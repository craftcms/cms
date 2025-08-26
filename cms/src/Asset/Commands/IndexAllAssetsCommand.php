<?php

namespace CraftCms\Cms\Asset\Commands;

use craft\console\Application;
use CraftCms\Cms\Asset\Commands\Concerns\IndexesAssets;
use CraftCms\Cms\Console\CraftCommand;
use Illuminate\Console\Command;
use Illuminate\Container\Attributes\Give;

final class IndexAllAssetsCommand extends Command
{
    use CraftCommand;
    use IndexesAssets;

    protected $signature = 'craft:index-assets:all
        {--cacheRemoteImages=false : Cache remote-stored images in the process.}
        {--createMissingAssets=true : Auto-create new asset records when missing.}
        {--deleteMissingAssets=false : Delete all the asset records that have their files missing.}
        {--deleteEmptyFolders=false : Delete empty folders.}
    ';

    protected $description = 'Re-indexes assets across all volumes.';

    protected $aliases = ['index-assets/all'];

    public function handle(#[Give('Craft')] Application $craft): void
    {
        $volumes = $craft->getVolumes()->getAllVolumes();

        if (empty($volumes)) {
            $this->components->warn('No volumes exist.');

            return;
        }

        $this->indexAssets($craft, $volumes);
    }
}
