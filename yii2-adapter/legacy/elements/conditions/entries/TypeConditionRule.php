<?php

declare(strict_types=1);

namespace craft\elements\conditions\entries;

use CraftCms\Yii2Adapter\Form\Concerns\LegacyMultiSelectConditionRule;

/** @deprecated 6.0.0 Use \CraftCms\Cms\Entry\Conditions\TypeConditionRule instead. */
class TypeConditionRule extends \CraftCms\Cms\Entry\Conditions\TypeConditionRule
{
    use LegacyMultiSelectConditionRule;
}
