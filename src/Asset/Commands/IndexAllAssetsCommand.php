<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Commands;

use craft\helpers\App;
use CraftCms\Cms\Asset\Commands\Concerns\IndexesAssets;
use CraftCms\Cms\Asset\Volumes;
use CraftCms\Cms\Console\CraftCommand;
use Illuminate\Console\Command;
use Override;
use Symfony\Component\Console\Input\InputOption;

class IndexAllAssetsCommand extends Command
{
    use CraftCommand;
    use IndexesAssets;

    #[Override]
    protected $signature = 'craft:index-assets:all
        {--createMissingAssets=true : Auto-create new asset records when missing.}
        {--deleteMissingAssets=false : Delete all the asset records that have their files missing.}
        {--deleteEmptyFolders=false : Delete empty folders.}
    ';

    #[Override]
    protected $description = 'Re-indexes assets across all volumes.';

    #[Override]
    protected $aliases = ['index-assets/all'];

    public function __construct()
    {
        parent::__construct();

        if (! App::isEphemeral()) {
            $this->getDefinition()->addOption(new InputOption(
                name: 'cacheRemoteImages',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'Cache remote-stored images in the process.',
                default: 'false',
            ));
        }
    }

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
