<?php

namespace craft\fields\conditions;

/**
 * Options field condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.6.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Field\Conditions\CountryFieldConditionRule} instead.
 */
class CountryFieldConditionRule extends \CraftCms\Cms\Field\Conditions\CountryFieldConditionRule
{
    use \craft\base\LegacyEventConstants;
}
