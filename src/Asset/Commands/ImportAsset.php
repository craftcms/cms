<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Commands;

use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Asset\Import\AssetImporter;
use CraftCms\Cms\Import\Commands\Import;
use CraftCms\Cms\Support\Facades\Volumes;
use Override;
use Symfony\Component\Console\Input\InputOption;

use function Laravel\Prompts\select;

class ImportAsset extends Import
{
    #[Override]
    protected $name = 'craft:import:asset';

    #[Override]
    protected $description = 'Imports Craft CMS Assets';

    #[Override]
    protected $aliases = ['import/asset'];

    #[Override]
    protected function configure(): void
    {
        parent::configure();

        $this->addOption('volume', null, InputOption::VALUE_OPTIONAL, 'The volume to import into.');
    }

    #[Override]
    public static function importerClass(): string
    {
        return AssetImporter::class;
    }

    #[Override]
    protected function getAdditionalOptions(): array
    {
        return array_merge(parent::getAdditionalOptions(), [
            'volume' => [
                'prompt' => fn () => select(
                    label: 'Which volume do you want to import into?',
                    options: Volumes::getAllVolumes()->mapWithKeys(fn (Volume $volume) => [$volume->uid => $volume->name])->all(),
                ),
            ],
        ]);
    }
}
