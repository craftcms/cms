<?php

namespace craft\conditions;

/**
 * ConditionInterface defines the common interface to be implemented by condition classes.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
interface ConditionInterface
{
    /**
     * Renders the HTML for the condition builder.
     *
     * @param array $options The builder options
     * @return string
     */
    public function getBuilderHtml(array $options = []): string;

    /**
     * Returns the config for this condition.
     *
     * @return array
     */
    public function getConfig(): array;

    /**
     * Returns the available rule types for this condition.
     *
     * @return string[]
     */
    public function getConditionRuleTypes(): array;

    /**
     * Validates a given rule to ensure it can be used with this condition.
     *
     * @param ConditionRuleInterface $rule
     * @return bool
     */
    function validateConditionRule(ConditionRuleInterface $rule): bool;
}
