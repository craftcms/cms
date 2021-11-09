<?php

namespace craft\conditions;

use craft\base\ComponentInterface;

/**
 * ConditionRuleInterface defines the common interface to be implemented by condition rule classes.
 *
 * A base implementation is provided by [[BaseConditionRule]].
 *
 * @property ConditionInterface $condition The condition associated with this rule
 * @property-read array $config The rule’s portable config
 * @property-read string $label The rule’s option label
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
interface ConditionRuleInterface extends ComponentInterface
{
    /**
     * Returns the rule’s option label.
     *
     * @return string
     */
    public function getLabel(): string;

    /**
     * Returns the rule’s portable config.
     *
     * @return array
     */
    public function getConfig(): array;

    /**
     * Returns the rule’s HTML for a condition builder.
     *
     * @param array $options The condition builder options
     * @return string
     */
    public function getHtml(array $options = []): string;

    /**
     * Sets the condition associated with this rule.
     *
     * @param ConditionInterface $condition
     */
    public function setCondition(ConditionInterface $condition): void;

    /**
     * Returns the condition associated with this rule.
     *
     * @return ConditionInterface
     */
    public function getCondition(): ConditionInterface;
}
