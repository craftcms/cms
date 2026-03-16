<?php

namespace craft\elements\conditions\addresses;

/**
 * Address organization condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Address\Conditions\OrganizationConditionRule} instead.
 */
class OrganizationConditionRule extends \CraftCms\Cms\Address\Conditions\OrganizationConditionRule
{
    use \craft\base\LegacyEventConstants;
}
