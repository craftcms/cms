<?php

declare(strict_types=1);

namespace craft\elements\conditions\users;

use CraftCms\Yii2Adapter\Form\Concerns\LegacyLightswitchConditionRule;

/** @deprecated 6.0.0 Use \CraftCms\Cms\User\Conditions\CredentialedConditionRule instead. */
class CredentialedConditionRule extends \CraftCms\Cms\User\Conditions\CredentialedConditionRule
{
    use LegacyLightswitchConditionRule;
}
