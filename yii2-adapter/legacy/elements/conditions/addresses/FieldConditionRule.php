<?php

declare(strict_types=1);

namespace craft\elements\conditions\addresses;

use CraftCms\Yii2Adapter\Form\Concerns\LegacyMultiSelectConditionRule;

/** @deprecated 6.0.0 Use \CraftCms\Cms\Address\Conditions\FieldConditionRule instead. */
class FieldConditionRule extends \CraftCms\Cms\Address\Conditions\FieldConditionRule
{
    use LegacyMultiSelectConditionRule;
}
