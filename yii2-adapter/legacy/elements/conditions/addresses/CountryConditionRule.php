<?php

namespace craft\elements\conditions\addresses;

/**
 * Address country condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Address\Conditions\CountryConditionRule} instead.
 */
class CountryConditionRule extends \CraftCms\Cms\Address\Conditions\CountryConditionRule
{
    use \craft\base\LegacyEventConstants;
}
