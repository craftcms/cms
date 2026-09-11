<?php

declare(strict_types=1);

namespace craft\elements\conditions\addresses;

use CraftCms\Yii2Adapter\Form\Concerns\LegacyMultiSelectConditionRule;

/** @deprecated 6.0.0 Use \CraftCms\Cms\Address\Conditions\CountryConditionRule instead. */
class CountryConditionRule extends \CraftCms\Cms\Address\Conditions\CountryConditionRule
{
    use LegacyMultiSelectConditionRule;
}
