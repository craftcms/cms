<?php

namespace craft\elements\conditions\users;

use craft\base\LegacyConditionEvents;
use craft\base\LegacyEventConstants;

/**
 * Last name condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\User\Conditions\LastNameConditionRule} instead.
 */
class LastNameConditionRule extends \CraftCms\Cms\User\Conditions\LastNameConditionRule
{
    use LegacyConditionEvents;
    use LegacyEventConstants;
}
