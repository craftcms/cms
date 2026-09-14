<?php

declare(strict_types=1);

namespace craft\elements\conditions\assets;

use CraftCms\Yii2Adapter\Form\Concerns\LegacyElementSelectConditionRule;

/** @deprecated 6.0.0 Use \CraftCms\Cms\Asset\Conditions\UploaderConditionRule instead. */
class UploaderConditionRule extends \CraftCms\Cms\Asset\Conditions\UploaderConditionRule
{
    use LegacyElementSelectConditionRule;
}
