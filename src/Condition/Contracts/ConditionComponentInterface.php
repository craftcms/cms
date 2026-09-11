<?php

declare(strict_types=1);

namespace CraftCms\Cms\Condition\Contracts;

use RuntimeException;

/**
 * ConditionComponentInterface defines the common interface to be implemented by condition rules and groups.
 *
 * @property ConditionInterface $condition The condition associated with this component
 * @property-read array<string, mixed> $config The component’s portable config
 */
interface ConditionComponentInterface
{
    /**
     * Returns the condition associated with this rule.
     */
    public function getCondition(): ConditionInterface;

    /**
     * Sets the condition associated with this rule.
     */
    public function setCondition(ConditionInterface $condition): void;

    /**
     * Returns the rule’s portable config.
     *
     * @return array<string, mixed>
     *
     * @throws RuntimeException if the rule is misconfigured
     */
    public function getConfig(): array;
}
