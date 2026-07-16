<?php

declare(strict_types=1);

namespace CraftCms\Cms\Console\Commands\Setup;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Support\File;
use Illuminate\Console\Command;
use Override;
use RuntimeException;

class PublishCommand extends Command
{
    use CraftCommand;

    #[Override]
    protected $signature = 'craft:setup:publish';

    #[Override]
    protected $description = 'Publish Craft\'s assets and configuration files to your project.';

    #[Override]
    protected $aliases = ['setup/publish'];

    public function handle(): void
    {
        $this->deletePublishedAssets();

        $this->call('vendor:publish', ['--tag' => 'craftcms-assets', '--force' => true]);
        $this->call('vendor:publish', ['--tag' => 'craftcms-config']);
        $this->call('vendor:publish', ['--tag' => 'craftcms-console']);
    }

    private function deletePublishedAssets(): void
    {
        $path = public_path('vendor/craft');

        if (! File::isDirectory($path)) {
            return;
        }

        if (! File::deleteDirectory($path)) {
            throw new RuntimeException("Unable to delete old Craft public asset directory: {$path}");
        }
    }
}
