<?php

declare(strict_types=1);

namespace CraftCms\Cms\Console\Commands\Setup;

use CraftCms\Cms\Console\CraftCommand;
use Illuminate\Console\Command;
use Override;

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
        $this->call('vendor:publish', ['--tag' => 'craftcms-assets', '--force' => true]);
        $this->call('vendor:publish', ['--tag' => 'craftcms-config']);
        $this->call('vendor:publish', ['--tag' => 'craftcms-console']);
    }
}
