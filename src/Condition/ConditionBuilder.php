<?php

declare(strict_types=1);

namespace CraftCms\Cms\Condition;

use CraftCms\Cms\Condition\Contracts\ConditionGroupInterface;
use CraftCms\Cms\Condition\Contracts\ConditionInterface;
use CraftCms\Cms\Condition\Contracts\ConditionRuleInterface;
use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormResolver;
use Throwable;

readonly class ConditionBuilder
{
    public function __construct(private Conditions $conditions, private FormResolver $forms) {}

    /**
     * @param  array<string, mixed>  $value
     * @param  array<string, mixed>  $config
     */
    public function createCondition(array $value, array $config): ConditionInterface
    {
        return $this->conditions->createCondition([
            ...$value,
            ...$config,
            'conditionRules' => $value['conditionRules'] ?? [],
        ]);
    }

    public function resolve(ConditionInterface $condition, bool $editable = true): ConditionBuilderPayload
    {
        $rules = [];
        foreach ($this->rules($condition->getConditionRules()) as $rule) {
            $rules[$rule->uid] = $this->resolveRule($rule, $editable);
        }

        $types = [];
        foreach ($condition->getSelectableConditionRules() as $value => $rule) {
            try {
                $label = $rule->getLabel();
            } catch (Throwable) {
                continue;
            }

            $types[] = [
                'value' => (string) $value,
                'label' => $label,
                'hint' => $rule->getLabelHint(),
                'showHint' => $rule->showLabelHint(),
                'group' => $rule->getGroupLabel(),
            ];
        }

        return new ConditionBuilderPayload(
            config: [
                ...$condition->getBuilderConfig(),
                'class' => $condition::class,
                'forProjectConfig' => $condition->forProjectConfig,
            ],
            value: $condition->getConfig(),
            rules: $rules,
            ruleTypes: collect($types)->sortBy(['group', 'label', 'hint'])->values()->all(),
            addRuleLabel: $condition->addRuleLabel,
        );
    }

    public function resolveRule(ConditionRuleInterface $rule, bool $editable = true): ConditionRulePayload
    {
        $context = new FormContext(
            namespace: ['_conditionRules', $rule->uid],
            mode: $editable ? ControlMode::Editable : ControlMode::Disabled,
            refreshable: true,
        );

        return new ConditionRulePayload(
            config: $rule->getConfig(),
            label: $rule->getLabel(),
            hint: $rule->getLabelHint(),
            showHint: $rule->showLabelHint(),
            form: $this->forms->resolve($rule->getForm($context), $context),
        );
    }

    /** @return iterable<ConditionRuleInterface> */
    private function rules(ConditionGroupInterface $group): iterable
    {
        foreach ($group->getRules() as $rule) {
            if ($rule instanceof ConditionGroupInterface) {
                yield from $this->rules($rule);
            } elseif ($rule instanceof ConditionRuleInterface) {
                yield $rule;
            }
        }
    }
}
