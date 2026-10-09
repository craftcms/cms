<?php

declare(strict_types=1);

namespace craft\elements\conditions\addresses;

use CraftCms\Yii2Adapter\Ui\Concerns\LegacyTextConditionRule;

/** @deprecated 6.0.0 Use \CraftCms\Cms\Address\Conditions\AddressLine2ConditionRule instead. */
class AddressLine2ConditionRule extends \CraftCms\Cms\Address\Conditions\AddressLine2ConditionRule
{
    use LegacyTextConditionRule;
}
