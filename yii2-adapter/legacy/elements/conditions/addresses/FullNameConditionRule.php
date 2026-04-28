<?php

namespace craft\elements\conditions\addresses;

/**
 * Address full name condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Address\Conditions\FullNameConditionRule} instead.
 */
class FullNameConditionRule extends \CraftCms\Cms\Address\Conditions\FullNameConditionRule
{
    use \craft\base\LegacyEventConstants;
}
