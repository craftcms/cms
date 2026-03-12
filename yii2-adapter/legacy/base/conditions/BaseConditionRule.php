<?php

namespace craft\base\conditions;

use craft\helpers\Html;

/**
 * BaseConditionRule provides a base implementation for condition rules.
 *
 * @property bool $isNew Whether the rule is new
 * @property ConditionInterface $condition
 * @property-read array $config The rule’s portable config
 * @property-read string $html The rule’s HTML for a condition builder
 * @property-read string $uiLabel The rule’s option label
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Condition\BaseConditionRule} instead.
 */
abstract class BaseConditionRule extends \CraftCms\Cms\Condition\BaseConditionRule
{
    use \craft\base\LegacyEventConstants;
}
