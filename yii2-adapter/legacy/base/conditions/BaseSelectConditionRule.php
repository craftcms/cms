<?php

namespace craft\base\conditions;

/**
 * BaseSelectConditionRule provides a base implementation for condition rules that are composed of a select input.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Condition\BaseSelectConditionRule} instead.
 */
abstract class BaseSelectConditionRule extends \CraftCms\Cms\Condition\BaseSelectConditionRule
{
    use \craft\base\LegacyEventConstants;
}
