<?php

namespace craft\elements\conditions\addresses;

/**
 * Address organization tax ID condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Address\Conditions\OrganizationTaxIdConditionRule} instead.
 */
class OrganizationTaxIdConditionRule extends \CraftCms\Cms\Address\Conditions\OrganizationTaxIdConditionRule
{
    use \craft\base\LegacyEventConstants;
}
