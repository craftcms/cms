<?php

declare(strict_types=1);

namespace craft\elements\conditions\users;

use CraftCms\Yii2Adapter\Form\Concerns\LegacyMultiSelectConditionRule;

/** @deprecated 6.0.0 Use \CraftCms\Cms\User\Conditions\GroupConditionRule instead. */
class GroupConditionRule extends \CraftCms\Cms\User\Conditions\GroupConditionRule
{
    use LegacyMultiSelectConditionRule;
}
