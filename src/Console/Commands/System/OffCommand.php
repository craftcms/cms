<?php

declare(strict_types=1);

namespace CraftCms\Cms\Console\Commands\System;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use Illuminate\Console\Command;
use Override;
use Throwable;

final class OffCommand extends Command
{
    use CraftCommand;
    use SetsSystemProjectConfig;

    #[Override]
    protected $signature = 'craft:off
        {--retry= : Number of seconds the Retry-After header should be set to for 503 responses. Set to 0 to remove it.}
    ';

    #[Override]
    protected $description = 'Takes the system offline.';

    public function handle(ProjectConfig $projectConfig): int
    {
        if (is_bool(Cms::config()->isSystemLive)) {
            $this->components->error('It\'s not possible to toggle the system status when the `isSystemLive` config setting is set.');

            return self::FAILURE;
        }

        if (! app()->isLive()) {
            $this->components->success('The system is already offline.');

            return self::SUCCESS;
        }

        try {
            $this->setProjectConfigValue($projectConfig, 'system.live', false);
        } catch (Throwable $throwable) {
            $this->components->error($throwable->getMessage());

            return self::FAILURE;
        }

        $this->components->success('The system is now offline.');

        $retry = $this->option('retry');

        if ($retry === null) {
            return self::SUCCESS;
        }

        $retry = (int) $retry;

        try {
            $this->setProjectConfigValue($projectConfig, 'system.retryDuration', $retry ?: null);
        } catch (Throwable $throwable) {
            $this->components->error($throwable->getMessage());

            return self::FAILURE;
        }

        $this->components->success($retry ? "The retry duration is now set to $retry." : 'The retry duration has been removed.');

        return self::SUCCESS;
    }
}
