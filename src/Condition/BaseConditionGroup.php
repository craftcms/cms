<?php

declare(strict_types=1);

namespace CraftCms\Cms\Condition;

use CraftCms\Cms\Condition\Contracts\ConditionComponentInterface;
use CraftCms\Cms\Condition\Contracts\ConditionGroupInterface;
use CraftCms\Cms\Condition\Contracts\ConditionInterface;
use CraftCms\Cms\Condition\Contracts\ConditionRuleInterface;
use CraftCms\Cms\Condition\Enums\GroupOperator;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * BaseConditionGroup provides a base implementation for condition groups.
 */
abstract class BaseConditionGroup implements ConditionGroupInterface
{
    /**
     * @var Collection<int, ConditionComponentInterface> The rules/groups this condition is configured with
     */
    private Collection $rules;

    private ConditionInterface $_condition;

    /**
     * @param  ConditionComponentInterface[]  $rules
     */
    public function __construct(public GroupOperator $operator = GroupOperator::And, array $rules = [])
    {
        $this->rules = Collection::make($rules);
    }

    public function getCondition(): ConditionInterface
    {
        return $this->_condition;
    }

    public function setCondition(ConditionInterface $condition): void
    {
        $this->_condition = $condition;

        foreach ($this->rules as $rule) {
            $rule->setCondition($condition);
        }
    }

    public function getConfig(): array
    {
        return [
            'operator' => $this->operator->value,
            'rules' => $this->rules
                ->map(function (ConditionComponentInterface $rule) {
                    try {
                        return $rule->getConfig();
                    } catch (RuntimeException) {
                        // The rule is misconfigured.
                        return null;
                    }
                })
                ->filter(fn (?array $config) => $config !== null)
                ->reject(fn (array $config) => isset($config['rules']) && $config['rules'] === [])
                ->values()
                ->all(),
        ];
    }

    public function getRules(): array
    {
        return $this->rules->all();
    }

    public function addRule(ConditionComponentInterface $rule): void
    {
        $this->rules->push($rule);
        $rule->setCondition($this->_condition);
    }

    public function removeRule(string $uid): void
    {
        $this->rules = $this->rules->filter(function (ConditionComponentInterface $rule) use ($uid) {
            if ($rule instanceof ConditionGroupInterface) {
                $rule->removeRule($uid);

                return true;
            }

            /** @var ConditionRuleInterface $rule */
            return $rule->uid !== $uid;
        });
    }
}
