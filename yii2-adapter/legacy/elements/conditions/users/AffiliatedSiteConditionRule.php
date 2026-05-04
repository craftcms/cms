<?php

namespace craft\elements\conditions\users;

use craft\base\LegacyConditionEvents;
use craft\base\LegacyEventConstants;

/**
 * Site condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.6.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\User\Conditions\AffiliatedSiteConditionRule} instead.
 */
class AffiliatedSiteConditionRule extends \CraftCms\Cms\User\Conditions\AffiliatedSiteConditionRule
{
    use LegacyConditionEvents;
    use LegacyEventConstants;
}
