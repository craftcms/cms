<?php

declare(strict_types=1);

namespace craft\elements\conditions\users;

use CraftCms\Yii2Adapter\Form\Concerns\LegacyTextConditionRule;

/** @deprecated 6.0.0 Use \CraftCms\Cms\User\Conditions\FirstNameConditionRule instead. */
class FirstNameConditionRule extends \CraftCms\Cms\User\Conditions\FirstNameConditionRule
{
    use LegacyTextConditionRule;
}
