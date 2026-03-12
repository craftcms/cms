<?php

namespace craft\fields\conditions;

/**
 * Options field condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.8.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Field\Conditions\LinkFieldConditionRule} instead.
 */
class LinkFieldConditionRule extends \CraftCms\Cms\Field\Conditions\LinkFieldConditionRule
{
    use \craft\base\LegacyEventConstants;
}
