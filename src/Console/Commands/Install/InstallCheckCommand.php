<?php

declare(strict_types=1);

namespace CraftCms\Cms\Console\Commands\Install;

use CraftCms\Cms\Cms;
use Illuminate\Console\Command;

final class InstallCheckCommand extends Command
{
    #[\Override]
    protected $signature = 'craft:install:check';

    #[\Override]
    protected $description = 'Checks whether Craft is already installed.';

    #[\Override]
    protected $aliases = ['install/check'];

    public function handle(): int
    {
        if (! Cms::isInstalled(true)) {
            $this->components->warn('Craft is not installed yet.');

            return self::FAILURE;
        }

        $this->components->success('Craft is installed.');

        return self::SUCCESS;
    }
}
