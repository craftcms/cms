<?php

declare(strict_types=1);

namespace CraftCms\Cms\Component\Validation\Contracts;

interface ValidatesWithScenarios extends Validatable
{
    /**
     * Sets the current validation scenario.
     *
     * Scenarios allow components to use different validation rules based on context.
     * For example, a 'create' scenario might require certain fields, while an 'update'
     * scenario might have different requirements.
     *
     * @param  string  $scenario  The scenario name to set
     */
    public function setScenario(string $scenario): void;

    /**
     * Returns the current validation scenario.
     *
     * @return string The active scenario name
     */
    public function getScenario(): string;

    /**
     * Returns a mapping of scenario names to their active attributes.
     *
     * Each scenario defines which attributes should be validated. The returned array
     * maps scenario names (keys) to either:
     * - An array of attribute names that should be validated in that scenario
     * - null to indicate all attributes should be validated
     *
     * Example:
     * ```php
     * [
     *     'create' => ['title', 'slug', 'body'],
     *     'update' => ['title', 'body'],
     *     'default' => null, // All attributes
     * ]
     * ```
     *
     * @return array<string, array<string>|null>
     */
    public function scenarios(): array;

    /**
     * Checks if the current scenario matches any of the provided scenarios.
     *
     * @param  string  ...$scenarios  One or more scenario names to check against
     * @return bool True if the current scenario matches any provided scenario
     */
    public function inScenarios(string ...$scenarios): bool;
}
