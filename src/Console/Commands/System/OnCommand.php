<?php

declare(strict_types=1);

namespace CraftCms\Cms\Console\Commands\System;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use Illuminate\Console\Command;
use Override;
use Throwable;

final class OnCommand extends Command
{
    use CraftCommand;
    use SetsSystemProjectConfig;

    #[Override]
    protected $signature = 'craft:on';

    #[Override]
    protected $description = 'Takes the system online.';

    public function handle(ProjectConfig $projectConfig): int
    {
        if (is_bool(Cms::config()->isSystemLive)) {
            $this->components->error('It\'s not possible to toggle the system status when the `isSystemLive` config setting is set.');

            return self::FAILURE;
        }

        if (app()->isLive()) {
            $this->components->success('The system is already online.');

            return self::SUCCESS;
        }

        try {
            $this->setProjectConfigValue($projectConfig, 'system.live', true);
        } catch (Throwable $throwable) {
            $this->components->error($throwable->getMessage());

            return self::FAILURE;
        }

        $this->components->success('The system is now online.');

        return self::SUCCESS;
    }
}
