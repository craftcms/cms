<?php

declare(strict_types=1);

namespace craft\elements\conditions;

use CraftCms\Yii2Adapter\Form\Concerns\LegacyTextConditionRule;

/** @deprecated 6.0.0 Use \CraftCms\Cms\Element\Conditions\UriConditionRule instead. */
class UriConditionRule extends \CraftCms\Cms\Element\Conditions\UriConditionRule
{
    use LegacyTextConditionRule;
}
