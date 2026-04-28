<?php

namespace craft\elements\conditions\addresses;

/**
 * Address postal code condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Address\Conditions\PostalCodeConditionRule} instead.
 */
class PostalCodeConditionRule extends \CraftCms\Cms\Address\Conditions\PostalCodeConditionRule
{
    use \craft\base\LegacyEventConstants;
}
