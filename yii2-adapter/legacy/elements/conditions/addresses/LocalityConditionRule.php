<?php

namespace craft\elements\conditions\addresses;

/**
 * Address locality condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Address\Conditions\LocalityConditionRule} instead.
 */
class LocalityConditionRule extends \CraftCms\Cms\Address\Conditions\LocalityConditionRule
{
    use \craft\base\LegacyEventConstants;
}
