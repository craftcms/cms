<?php

namespace craft\elements\conditions\users;

use craft\base\LegacyConditionEvents;
use craft\base\LegacyEventConstants;

/**
 * Last login date condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\User\Conditions\LastLoginDateConditionRule} instead.
 */
class LastLoginDateConditionRule extends \CraftCms\Cms\User\Conditions\LastLoginDateConditionRule
{
    use LegacyConditionEvents;
    use LegacyEventConstants;
}
