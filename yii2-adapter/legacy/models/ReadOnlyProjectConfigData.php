<?php

namespace craft\models;

use craft\base\Model;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @since 4.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\ProjectConfig\Data\ReadOnlyProjectConfigData} instead.
     */
    class ReadOnlyProjectConfigData extends Model
    {
    }
}

class_alias(\CraftCms\Cms\ProjectConfig\Data\ReadOnlyProjectConfigData::class, ReadOnlyProjectConfigData::class);
