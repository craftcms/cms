<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Commands;

use craft\console\Application;
use craft\helpers\App;
use CraftCms\Cms\Asset\Commands\Concerns\IndexesAssets;
use CraftCms\Cms\Console\CraftCommand;
use Illuminate\Console\Command;
use Illuminate\Container\Attributes\Give;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Symfony\Component\Console\Input\InputOption;

final class IndexOneAssetCommand extends Command implements PromptsForMissingInput
{
    use CraftCommand;
    use IndexesAssets;

    protected $signature = 'craft:index-assets:one
        {handle : The handle of the volume to index. You can optionally provide a volume sub-path, e.g. `php craft index-assets/one volume-handle/path/to/folder`.}
        {startAt=0 : Index of the asset to start with.}
        {--createMissingAssets=true : Auto-create new asset records when missing.}
        {--deleteMissingAssets=false : Delete all the asset records that have their files missing.}
        {--deleteEmptyFolders=false : Delete empty folders.}
    ';

    protected $description = 'Re-indexes assets from the given volume handle.';

    protected $aliases = ['index-assets/one', 'index-assets'];

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
        $handle = $this->argument('handle');
        $path = '';

        if (str_contains($handle, '/')) {
            $parts = explode('/', $handle);
            $handle = array_shift($parts);
            $path = implode('/', $parts);
        }

        $volume = $craft->getVolumes()->getVolumeByHandle($handle);

        if (! $volume) {
            $this->components->error("No volume exists with the handle “{$handle}”.");

            return;
        }

        $this->indexAssets($craft, [$volume], $path, (int) $this->argument('startAt'));
    }
}
