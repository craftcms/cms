<?php

namespace craft\conditions;

use Illuminate\Support\Collection;

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
     * Sets the available rule types for this condition.
     *
     * @param string[] $conditionRuleTypes
     */
    public function setConditionRuleTypes(array $conditionRuleTypes = []): void;

    /**
     * Returns the rules this condition is configured with.
     *
     * @return Collection
     */
    public function getConditionRules(): Collection;
}
