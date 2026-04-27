<?php

namespace craft\elements\conditions;

/**
 * ID condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Conditions\UriConditionRule} instead.
 */
class UriConditionRule extends \CraftCms\Cms\Element\Conditions\UriConditionRule
{
    use \craft\base\LegacyEventConstants;
}
