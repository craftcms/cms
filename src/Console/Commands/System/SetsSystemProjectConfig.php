<?php

declare(strict_types=1);

namespace CraftCms\Cms\Console\Commands\System;

use CraftCms\Cms\ProjectConfig\ProjectConfig;

trait SetsSystemProjectConfig
{
    private function setProjectConfigValue(ProjectConfig $projectConfig, string $path, mixed $value): void
    {
        $originalReadOnly = $projectConfig->readOnly;
        $originalWriteYamlAutomatically = $projectConfig->writeYamlAutomatically;

        try {
            $projectConfig->readOnly = false;
            $projectConfig->writeYamlAutomatically = false;
            $projectConfig->set($path, $value, null, false);
            $projectConfig->saveModifiedConfigData();
            $projectConfig->reset();
        } finally {
            $projectConfig->readOnly = $originalReadOnly;
            $projectConfig->writeYamlAutomatically = $originalWriteYamlAutomatically;
        }
    }
}
