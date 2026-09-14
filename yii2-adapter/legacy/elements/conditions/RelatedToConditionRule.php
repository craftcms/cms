<?php

declare(strict_types=1);

namespace craft\elements\conditions;

use CraftCms\Yii2Adapter\Form\Concerns\LegacyRelatedToConditionRule;

/** @deprecated 6.0.0 Use \CraftCms\Cms\Element\Conditions\RelatedToConditionRule instead. */
class RelatedToConditionRule extends \CraftCms\Cms\Element\Conditions\RelatedToConditionRule
{
    use LegacyRelatedToConditionRule;
}
