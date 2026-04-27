<?php

namespace craft\elements\conditions;

/**
 * Element has descendants condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.3.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Conditions\HasDescendantsRule} instead.
 */
class HasDescendantsRule extends \CraftCms\Cms\Element\Conditions\HasDescendantsRule
{
    use \craft\base\LegacyEventConstants;
}
