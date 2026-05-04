<?php

namespace craft\elements\conditions\users;

use craft\base\LegacyConditionEvents;
use craft\base\LegacyEventConstants;

/**
 * Email condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\User\Conditions\EmailConditionRule} instead.
 */
class EmailConditionRule extends \CraftCms\Cms\User\Conditions\EmailConditionRule
{
    use LegacyConditionEvents;
    use LegacyEventConstants;
}
