<?php

declare(strict_types=1);

namespace CraftCms\Cms\Console\Commands\Install;

use craft\console\Application;
use Illuminate\Console\Command;
use Illuminate\Container\Attributes\Give;

final class InstallCheckCommand extends Command
{
    protected $signature = 'craft:install:check';

    protected $description = 'Checks whether Craft is already installed.';

    protected $aliases = ['install/check'];

    public function handle(#[Give('Craft')] Application $craft): int
    {
        if (! $craft->getIsInstalled(true)) {
            $this->components->warn('Craft is not installed yet.');

            return self::FAILURE;
        }

        $this->components->success('Craft is installed.');

        return self::SUCCESS;
    }
}
