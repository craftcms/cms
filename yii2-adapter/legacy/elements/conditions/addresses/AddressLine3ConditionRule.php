<?php

declare(strict_types=1);

namespace craft\elements\conditions\addresses;

use CraftCms\Yii2Adapter\Form\Concerns\LegacyTextConditionRule;

/** @deprecated 6.0.0 Use \CraftCms\Cms\Address\Conditions\AddressLine3ConditionRule instead. */
class AddressLine3ConditionRule extends \CraftCms\Cms\Address\Conditions\AddressLine3ConditionRule
{
    use LegacyTextConditionRule;
}
