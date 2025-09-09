<?php

namespace craft\helpers;

use CraftCms\Cms\ProjectConfig\ProjectConfigHelper;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\ProjectConfig\ProjectConfigHelper} instead.
     */
    class ProjectConfig
    {
    }
}

class_alias(ProjectConfigHelper::class, ProjectConfig::class);
