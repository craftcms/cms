<?php

namespace craft\base\conditions;

/**
 * BaseElementSelectConditionRule provides a base implementation for element query condition rules that are composed of an element select input.
 *
 * @property int|null $elementId
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Condition\BaseElementSelectConditionRule} instead.
 */
abstract class BaseElementSelectConditionRule extends \CraftCms\Cms\Condition\BaseElementSelectConditionRule
{
    use \craft\base\LegacyEventConstants;
}
