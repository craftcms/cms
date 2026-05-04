<?php

namespace craft\elements\conditions\users;

use craft\base\LegacyConditionEvents;
use craft\base\LegacyEventConstants;

/**
 * Username condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\User\Conditions\UsernameConditionRule} instead.
 */
class UsernameConditionRule extends \CraftCms\Cms\User\Conditions\UsernameConditionRule
{
    use LegacyConditionEvents;
    use LegacyEventConstants;
}
