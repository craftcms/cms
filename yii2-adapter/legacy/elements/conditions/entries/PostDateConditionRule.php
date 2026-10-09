<?php

declare(strict_types=1);

namespace craft\elements\conditions\entries;

use CraftCms\Yii2Adapter\Ui\Concerns\LegacyDateRangeConditionRule;

/** @deprecated 6.0.0 Use \CraftCms\Cms\Entry\Conditions\PostDateConditionRule instead. */
class PostDateConditionRule extends \CraftCms\Cms\Entry\Conditions\PostDateConditionRule
{
    use LegacyDateRangeConditionRule;
}
