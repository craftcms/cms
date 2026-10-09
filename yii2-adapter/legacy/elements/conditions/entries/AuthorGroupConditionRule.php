<?php

declare(strict_types=1);

namespace craft\elements\conditions\entries;

use CraftCms\Yii2Adapter\Ui\Concerns\LegacyMultiSelectConditionRule;

/** @deprecated 6.0.0 Use \CraftCms\Cms\Entry\Conditions\AuthorGroupConditionRule instead. */
class AuthorGroupConditionRule extends \CraftCms\Cms\Entry\Conditions\AuthorGroupConditionRule
{
    use LegacyMultiSelectConditionRule;
}
