<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Migration;
use CraftCms\Cms\ProjectConfig\ProjectConfig;

return new class extends Migration
{
    public function up(): void
    {
        $projectConfig = app(ProjectConfig::class);
        /** @var array<string, array<string, mixed>>|null $filesystems */
        $filesystems = $projectConfig->get(ProjectConfig::PATH_FS);

        if (! is_array($filesystems)) {
            return;
        }

        $muteEvents = $projectConfig->muteEvents;
        $projectConfig->muteEvents = true;

        try {
            foreach ($filesystems as $handle => $config) {
                /** @var array<string, mixed> $settings */
                $settings = $config['settings'] ?? [];
                $changed = false;

                foreach (['hasUrls', 'url'] as $attribute) {
                    if (! array_key_exists($attribute, $config)) {
                        continue;
                    }

                    if (! array_key_exists($attribute, $settings)) {
                        $settings[$attribute] = $config[$attribute];
                    }

                    unset($config[$attribute]);
                    $changed = true;
                }

                if (! $changed) {
                    continue;
                }

                $config['settings'] = $settings;
                $projectConfig->set(
                    ProjectConfig::PATH_FS.".$handle",
                    $config,
                    'Move filesystem URL values into settings',
                );
            }
        } finally {
            $projectConfig->muteEvents = $muteEvents;
        }

        $projectConfig->saveModifiedConfigData();
    }
};
