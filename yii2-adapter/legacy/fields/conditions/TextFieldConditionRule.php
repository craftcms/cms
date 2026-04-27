<?php

namespace craft\fields\conditions;

/**
 * Text field condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Field\Conditions\TextFieldConditionRule} instead.
 */
class TextFieldConditionRule extends \CraftCms\Cms\Field\Conditions\TextFieldConditionRule
{
    use \craft\base\LegacyEventConstants;
}
