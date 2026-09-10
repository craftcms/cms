<?php

declare(strict_types=1);

namespace craft\elements\conditions;

use CraftCms\Yii2Adapter\Form\Concerns\LegacyRelatedToConditionRule;

/** @deprecated 6.0.0 Use \CraftCms\Cms\Element\Conditions\NotRelatedToConditionRule instead. */
class NotRelatedToConditionRule extends \CraftCms\Cms\Element\Conditions\NotRelatedToConditionRule
{
    use LegacyRelatedToConditionRule;
}
