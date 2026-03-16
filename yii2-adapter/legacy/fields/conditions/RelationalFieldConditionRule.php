<?php

namespace craft\fields\conditions;

/**
 * Relational field condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Field\Conditions\RelationalFieldConditionRule} instead.
 */
class RelationalFieldConditionRule extends \CraftCms\Cms\Field\Conditions\RelationalFieldConditionRule
{
    use \craft\base\LegacyEventConstants;
}
