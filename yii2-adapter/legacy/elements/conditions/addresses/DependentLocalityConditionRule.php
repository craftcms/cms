<?php

namespace craft\elements\conditions\addresses;

/**
 * Address dependent locality condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Address\Conditions\DependentLocalityConditionRule} instead.
 */
class DependentLocalityConditionRule extends \CraftCms\Cms\Address\Conditions\DependentLocalityConditionRule
{
    use \craft\base\LegacyEventConstants;
}
