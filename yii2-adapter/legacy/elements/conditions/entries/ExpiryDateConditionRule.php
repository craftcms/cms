<?php

namespace craft\elements\conditions\entries;

/**
 * Element expiry date condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Entry\Conditions\ExpiryDateConditionRule} instead.
 */
class ExpiryDateConditionRule extends \CraftCms\Cms\Entry\Conditions\ExpiryDateConditionRule
{
    use \craft\base\LegacyEventConstants;
}
