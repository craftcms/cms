<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Commands;

use craft\console\Application;
use craft\helpers\App;
use CraftCms\Cms\Asset\Commands\Concerns\IndexesAssets;
use CraftCms\Cms\Console\CraftCommand;
use Illuminate\Console\Command;
use Illuminate\Container\Attributes\Give;
use Symfony\Component\Console\Input\InputOption;

final class IndexAllAssetsCommand extends Command
{
    use CraftCommand;
    use IndexesAssets;

    #[\Override]
    protected $signature = 'craft:index-assets:all
        {--createMissingAssets=true : Auto-create new asset records when missing.}
        {--deleteMissingAssets=false : Delete all the asset records that have their files missing.}
        {--deleteEmptyFolders=false : Delete empty folders.}
    ';

    #[\Override]
    protected $description = 'Re-indexes assets across all volumes.';

    #[\Override]
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
