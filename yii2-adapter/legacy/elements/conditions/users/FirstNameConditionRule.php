<?php

namespace craft\elements\conditions\users;

use craft\base\LegacyConditionEvents;
use craft\base\LegacyEventConstants;

/**
 * First name condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\User\Conditions\FirstNameConditionRule} instead.
 */
class FirstNameConditionRule extends \CraftCms\Cms\User\Conditions\FirstNameConditionRule
{
    use LegacyConditionEvents;
    use LegacyEventConstants;
}
