<?php

namespace craft\models;

/**
 * @since 4.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\ProjectConfig\Data\ReadOnlyProjectConfigData} instead.
 */
class ReadOnlyProjectConfigData extends \CraftCms\Cms\ProjectConfig\Data\ReadOnlyProjectConfigData
{
    use \craft\base\LegacyEventConstants;
}
