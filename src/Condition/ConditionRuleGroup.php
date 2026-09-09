<?php

declare(strict_types=1);

namespace CraftCms\Cms\Condition;

use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Condition\Contracts\ConditionRuleInterface;
use Illuminate\Support\Collection;

/**
 * ConditionRuleGroup contains a group of condition rules.
 */
class ConditionRuleGroup extends Component
{
    /**
     * @var Collection<int, ConditionRuleInterface> The rules this condition is configured with
     */
    public array $conditionRules;

    public function __construct(object|array $config = [])
    {
        parent::__construct($config);

        if (! isset($this->conditionRules)) {
            $this->conditionRules = Collection::make();
        }
    }

    public function getConfig(): array
    {
        return [
            'conditionRules' => $this->conditionRules
                ->map(function (ConditionRuleInterface $rule) {
                    try {
                        return $rule->getConfig();
                    } catch (RuntimeException) {
                        // The rule is misconfigured
                        return null;
                    }
                })
                ->filter(fn (?array $config) => $config !== null)
                ->values()
                ->all(),
        ];
    }
}
