<?php

namespace craft\base\conditions;

/**
 * BaseMultiSelectConditionRule provides a base implementation for condition rules that are composed of a multi-select input.
 *
 * @property string[] $values
 * @since 4.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Condition\BaseMultiSelectConditionRule} instead.
 */
abstract class BaseMultiSelectConditionRule extends \CraftCms\Cms\Condition\BaseMultiSelectConditionRule
{
    use \craft\base\LegacyEventConstants;
}
