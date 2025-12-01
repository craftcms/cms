<?php

namespace craft\base\conditions;

use yii\base\InvalidArgumentException;

/**
 * ConditionInterface defines the common interface to be implemented by condition classes.
 *
 * A base implementation is provided by [[BaseCondition]].
 *
 * @mixin BaseCondition
 * @phpstan-require-extends BaseCondition
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
interface ConditionInterface
{
    /**
     * Renders the HTML for the condition builder, including its outer container element.
     *
     * @return string
     */
    public function getBuilderHtml(): string;

    /**
     * Renders the inner HTML for the condition builder, excluding its outer container element.
     *
     * @param bool $autofocusAddButton Whether the Add Rule button should be autofocused
     * @return string
     */
    public function getBuilderInnerHtml(bool $autofocusAddButton = false): string;

    /**
     * Returns configuration that should be maintained for the builder.
     *
     * @return array
     * @since 5.1.0
     */
    public function getBuilderConfig(): array;

    /**
     * Returns the condition’s portable config.
     *
     * @return array
     */
    public function getConfig(): array;

    /**
     * Creates a condition rule instance.
     *
     * @param array|string $config The condition class or configuration array
     * @phpstan-param array{class: string}|array{type:string}|string $config The condition class or configuration array
     * @return ConditionRuleInterface
     * @throws InvalidArgumentException if the condition rule does not implement [[ConditionRuleInterface]]
     */
    public function createConditionRule(array|string $config): ConditionRuleInterface;

    /**
     * Returns the selectable rules for the condition, indexed by type.
     *
     * @return ConditionRuleInterface[]
     */
    public function getSelectableConditionRules(): array;

    /**
     * Returns the rules this condition is configured with.
     *
     * @return ConditionRuleInterface[]
     */
    public function getConditionRules(): array;

    /**
     * Sets the rules this condition should be configured with.
     *
     * @param ConditionRuleInterface[]|array[] $rules
     * @throws InvalidArgumentException if any of the rules are not selectable
     */
    public function setConditionRules(array $rules): void;

    /**
     * Adds a rule to the condition.
     *
     * @param ConditionRuleInterface $rule
     * @throws InvalidArgumentException if the rule is not selectable
     */
    public function addConditionRule(ConditionRuleInterface $rule): void;
}
