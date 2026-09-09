<?php

declare(strict_types=1);

namespace craft\elements\conditions\entries;

use CraftCms\Yii2Adapter\Form\Concerns\LegacyMultiSelectConditionRule;

/** @deprecated 6.0.0 Use \CraftCms\Cms\Entry\Conditions\SectionConditionRule instead. */
class SectionConditionRule extends \CraftCms\Cms\Entry\Conditions\SectionConditionRule
{
    use LegacyMultiSelectConditionRule;
}
