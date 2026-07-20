<?php

declare(strict_types=1);

namespace CraftCms\Cms\Console\Commands\Setup;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Plugin\Plugins;
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
    protected $description = 'Publish Craft and plugin assets and configuration files to your project.';

    #[Override]
    protected $aliases = ['setup/publish'];

    public function handle(Plugins $plugins): void
    {
        if ($this->assetsAreSymlinked()) {
            $this->warn(sprintf(
                'Skipping asset publishing: %s is a symlink, so assets are already served from its target. Remove the symlink if you want physical copies published.',
                public_path('vendor/craft'),
            ));
        } else {
            $this->deletePublishedAssets();
            $this->call('vendor:publish', ['--tag' => 'craftcms-assets', '--force' => true]);
        }

        $this->call('vendor:publish', ['--tag' => 'craftcms-config']);
        $this->call('vendor:publish', ['--tag' => 'craftcms-console']);

        $plugins->publishPluginAssets();
    }

    /**
     * Whether `public/vendor/craft` is a symlink (a common dev setup pointing
     * straight at the cms-assets resources). Deleting through it would empty
     * the link's target — `File::deleteDirectory()` follows a top-level
     * symlink — so publishing must leave it alone entirely.
     */
    private function assetsAreSymlinked(): bool
    {
        return is_link(public_path('vendor/craft'));
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
