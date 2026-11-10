<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Commands;

use CraftCms\Cms\Asset\Commands\Concerns\IndexesAssets;
use CraftCms\Cms\Asset\Volumes;
use CraftCms\Cms\Console\CraftCommand;
use Illuminate\Console\Command;
use Override;

/**
 * @since 6.0.0
 */
class IndexAllAssetsCommand extends Command
{
    use CraftCommand;
    use IndexesAssets;

    #[Override]
    protected $signature = 'craft:index-assets:all
        {--createMissingAssets=true : Auto-create new asset records when missing.}
        {--deleteMissingAssets=false : Delete all the asset records that have their files missing.}
        {--deleteEmptyFolders=false : Delete empty folders.}
        {--cacheRemoteImages=false : Cache remote-stored images in the process.}
        {--analyzeImages=false : Sample missing image colors and metadata, and queue images for AI tasks.}
    ';

    #[Override]
    protected $description = 'Re-indexes assets across all volumes.';

    #[Override]
    protected $aliases = ['index-assets/all'];

    public function handle(Volumes $volumes): void
    {
        $volumes = $volumes->getAllVolumes();

        if ($volumes->isEmpty()) {
            $this->components->warn('No volumes exist.');

            return;
        }

        $this->indexAssets($volumes->all());
    }
}
