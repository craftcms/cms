<?php

declare(strict_types=1);

namespace CraftCms\Cms\Condition;

use CraftCms\Cms\Condition\Contracts\ConditionInterface;
use CraftCms\Cms\Condition\Contracts\ConditionRuleInterface;
use CraftCms\Cms\Ui\UiPayload;
use Throwable;

/**
 * A condition's rules, in groups too, as the condition builder renders them,
 * so a summary of the applied filters reads exactly like the builder.
 */
final readonly class ConditionSummary
{
    public function __construct(private ConditionBuilder $builder) {}

    /**
     * @return list<array{uid: string|null, label: string, ui: UiPayload|null}>
     */
    public function rules(ConditionInterface $condition): array
    {
        $rules = $condition->getConditionRules()->findRules(
            fn ($rule) => $rule instanceof ConditionRuleInterface,
        );

        return array_map(function (ConditionRuleInterface $rule): array {
            // A rule that can't render still gets a chip, by name.
            try {
                $ui = $this->builder->resolveRule($rule, editable: false)->ui;
            } catch (Throwable $e) {
                report($e);
                $ui = null;
            }

            return [
                'uid' => $rule->uid ?? null,
                'label' => $rule->getLabel(),
                'ui' => $ui,
            ];
        }, $rules);
    }
}
