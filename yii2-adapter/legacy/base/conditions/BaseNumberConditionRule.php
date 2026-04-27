<?php

namespace craft\base\conditions;

/**
 * BaseNumberConditionRule provides a base implementation for condition rules that are composed of a number input.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Condition\BaseNumberConditionRule} instead.
 */
abstract class BaseNumberConditionRule extends \CraftCms\Cms\Condition\BaseNumberConditionRule
{
    use \craft\base\LegacyEventConstants;
}
