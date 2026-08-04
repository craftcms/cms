<?php

namespace craft\base\conditions;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * BaseCondition provides a base implementation for conditions.
     *
     * @property ConditionRuleInterface[] $conditionRules The rules this condition is configured with
     * @property-read array $config The condition’s portable config
     * @property-read string $builderHtml The HTML for the condition builder, including its outer container element
     * @property-read string $builderInnerHtml The inner HTML for the condition builder, excluding its outer container element
     * @property-read string[]|array{class: string}[] $conditionRuleTypes The available rule types for this condition
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     * @since 4.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Condition\BaseCondition} instead.
     */
    abstract class BaseCondition extends \CraftCms\Cms\Condition\BaseCondition
    {
    }
}
