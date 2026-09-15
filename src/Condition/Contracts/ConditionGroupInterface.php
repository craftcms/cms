<?php

declare(strict_types=1);

namespace CraftCms\Cms\Condition\Contracts;

use Closure;
use CraftCms\Cms\Condition\BaseConditionGroup;

/**
 * ConditionGroupInterface defines the common interface to be implemented by condition group classes.
 *
 * A base implementation is provided by {@see BaseConditionGroup}.
 *
 * @mixin BaseConditionGroup
 *
 * @phpstan-require-extends BaseConditionGroup
 */
interface ConditionGroupInterface extends ConditionComponentInterface
{
    /**
     * Returns the nested rules and groups.
     *
     * @return ConditionComponentInterface[]
     */
    public function getRules(): array;

    /**
     * Returns all the rules that match the given callback function, recursively.
     *
     * @param  Closure(ConditionRuleInterface): bool  $callback
     * @return ConditionRuleInterface[]
     */
    public function findRules(Closure $callback): array;

    /**
     * Adds a new rule or group.
     */
    public function addRule(ConditionComponentInterface $rule): void;

    /**
     * Removes a rule via its UUID.
     */
    public function removeRule(string $uid): void;
}
