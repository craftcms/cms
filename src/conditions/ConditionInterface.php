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
     * @return string
     */
    public function getBuilderHtml(): string;

    /**
     * Returns the config for this condition.
     *
     * @return array
     */
    public function getConfig(): array;
}
