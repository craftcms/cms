<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Schema;

use CraftCms\Cms\Condition\BaseConditionGroup;
use CraftCms\Cms\Condition\BaseConditionRule;
use CraftCms\Cms\Condition\BaseDateRangeConditionRule;
use CraftCms\Cms\Condition\BaseElementSelectConditionRule;
use CraftCms\Cms\Condition\BaseLightswitchConditionRule;
use CraftCms\Cms\Condition\BaseMultiSelectConditionRule;
use CraftCms\Cms\Condition\BaseNumberConditionRule;
use CraftCms\Cms\Condition\BaseSelectConditionRule;
use CraftCms\Cms\Condition\BaseTextConditionRule;
use CraftCms\Cms\Condition\Contracts\ConditionGroupInterface;
use CraftCms\Cms\Condition\Contracts\ConditionInterface;
use CraftCms\Cms\Condition\Contracts\ConditionRuleInterface;
use CraftCms\Cms\Condition\Enums\GroupOperator;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Field\BaseRelationField;
use CraftCms\Cms\Field\Conditions\Contracts\FieldConditionRuleInterface;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\ProjectConfig\ProjectConfigHelper;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\User\Elements\User;
use Mcp\Exception\ToolCallException;
use ReflectionMethod;
use Throwable;

/**
 * Describes the rules a condition accepts and builds conditions from MCP input, the same way wherever conditions are
 * configured: element source sidebars, field layout visibility, and relation field selection.
 *
 * @since 6.0.0
 */
readonly class ConditionSchema
{
    /** The input schema of a condition config. */
    public const array Schema = [
        'type' => ['object', 'null'],
        'description' => '{conditionRules: {operator: "and" or "or", rules: [...]}}, each rule built from a condition_schema template; a plain list of rules matches all of them. A rule can also be a nested {operator, rules} group. The class, elementType, fieldContext, and forQuery that reads return, and each rule’s uid, can be sent back or left out.',
        'properties' => [
            'conditionRules' => ['type' => ['object', 'array'], 'additionalProperties' => true, 'items' => ['type' => 'object', 'additionalProperties' => true]],
        ],
        'additionalProperties' => true,
    ];

    /**
     * Returns the condition Customize Sources uses for an element type’s custom sources.
     *
     * @param  class-string<ElementInterface>  $elementType
     */
    public function forElementSources(string $elementType): ElementConditionInterface
    {
        $condition = $elementType::createCondition();
        $condition->forProjectConfig = true;

        return $condition;
    }

    /**
     * Returns the visibility condition of a field layout’s tabs and elements, as the field layout designer builds it.
     *
     * @param  class-string<ElementInterface>  $elementType
     * @param  FieldLayout|null  $layout  The layout whose fields element rules can use, or null for all of the element type’s layouts
     * @param  'element'|'user'  $kind
     */
    public function forFieldLayout(string $elementType, ?FieldLayout $layout, string $kind): ConditionInterface
    {
        if ($kind === 'user') {
            $condition = User::createCondition();
        } else {
            $condition = $elementType::createCondition();

            if ($layout) {
                $condition->setFieldLayouts([$layout]);
            }
        }

        $condition->forProjectConfig = true;

        return $condition;
    }

    /** Returns the selectable elements condition of a relation field, as its settings build it. */
    public function forRelationSelection(BaseRelationField $field): ElementConditionInterface
    {
        $condition = $this->call($field, 'createSelectionCondition');

        if (! $condition instanceof ElementConditionInterface) {
            throw new ToolCallException(sprintf('%s fields don’t have selection conditions.', $field::displayName()));
        }

        $condition->forProjectConfig = true;

        return $condition;
    }

    /**
     * Returns the rules a condition accepts, with templates and notes on filling them in.
     *
     * @return array<string, mixed>
     */
    public function describe(ConditionInterface $condition): array
    {
        $rules = [];

        // Field rules match every layout that uses the field with the same handle and label, so list each once
        foreach ($condition->getSelectableConditionRules() as $rule) {
            $description = $this->describeRule($rule);

            if ($description === null) {
                continue;
            }

            $rules[Json::encode([$description['class'], $description['template']['fieldUid'] ?? null, $description['label']])] ??= $description;
        }

        return [
            'conditionClass' => $condition::class,
            'notes' => [
                'A condition is {conditionRules: {operator, rules: [...]}}. Copy a rule’s template, which holds every setting the rule saves, set its values from the rule’s operators and options, and add it to rules. Saving a condition replaces all of its rules, so send the existing ones too. null or no rules removes the condition.',
                'operator and matches when all rules match, and or when any does. A rule can also be a nested {operator, rules} group. One rule with several values matches any of them.',
                'Rules without operators take no operator.',
                'values and value hold option values, not labels: UIDs for sections, entry types, sites, and groups, and option values for dropdown and similar fields.',
                'A field rule’s field shows its field’s handle for reference; rules identify the field with fieldUid. Keep a field rule’s layoutElementUid from the template, even when it points at another layout, such as for a new layout: the rule matches the field in every layout where it has the same handle and label.',
            ],
            'rules' => array_values($rules),
        ];
    }

    /**
     * Builds a condition from input and returns its normalized config, rejecting rules it would drop.
     *
     * @param  ConditionInterface  $condition  A new condition from one of the for*() methods
     * @return array<string, mixed>
     */
    public function normalize(ConditionInterface $condition, mixed $input, string $path): array
    {
        $input ??= [];

        if (! is_array($input)) {
            throw new ToolCallException("$path must be an object.");
        }

        // Field settings are read in project config’s packed form
        $input = ProjectConfigHelper::unpackAssociativeArray($input);

        if (isset($input['class']) && $input['class'] !== $condition::class) {
            throw new ToolCallException(sprintf('%s.class must be %s, or omitted.', $path, $condition::class));
        }

        $rules = [];
        $group = $this->group($condition, $input['conditionRules'] ?? [], "$path.conditionRules", $rules);
        $condition->setConditionRules($group);
        $config = $condition->getConfig();

        // Craft leaves out rules it can’t save the config of, such as field rules for fields it can’t find
        $kept = [];
        array_walk_recursive($config['conditionRules'], function (mixed $value, string|int $key) use (&$kept): void {
            if ($key === 'uid') {
                $kept[] = $value;
            }
        });

        foreach ($rules as $rulePath => $rule) {
            if (! in_array($rule->uid, $kept, true)) {
                throw new ToolCallException(sprintf('%s (%s) isn’t available here. Use a rule from condition_schema.', $rulePath, $rule::class));
            }
        }

        return $config;
    }

    /**
     * Returns whether a normalized condition config has any rules.
     *
     * @param  array<string, mixed>  $config
     */
    public function hasRules(array $config): bool
    {
        return ($config['conditionRules']['rules'] ?? []) !== [];
    }

    /**
     * Builds a rule group from input: a list of rules, or an {operator, rules} group.
     *
     * @param  array<string, ConditionRuleInterface>  $rules  The rules built so far, by path
     */
    private function group(ConditionInterface $condition, mixed $input, string $path, array &$rules): ConditionGroupInterface
    {
        if (! is_array($input)) {
            throw new ToolCallException("$path must be a list of rules or an {operator, rules} group.");
        }

        $operator = GroupOperator::And;
        $items = $input;

        if (! array_is_list($input)) {
            $operator = GroupOperator::tryFrom(strtolower((string) ($input['operator'] ?? 'and')))
                ?? throw new ToolCallException("$path.operator must be and or or.");
            $items = $input['rules'] ?? [];
            $path .= '.rules';

            if (! is_array($items) || ! array_is_list($items)) {
                throw new ToolCallException("$path must be a list.");
            }
        }

        $group = $condition::createGroup();
        $group->setCondition($condition);

        if ($group instanceof BaseConditionGroup) {
            $group->operator = $operator;
        } elseif ($operator !== GroupOperator::And) {
            throw new ToolCallException("$path: this condition only combines rules with and.");
        }

        foreach ($items as $i => $item) {
            $itemPath = "{$path}[$i]";

            if (is_array($item) && ! isset($item['class']) && isset($item['rules'])) {
                $group->addRule($this->group($condition, $item, $itemPath, $rules));

                continue;
            }

            $class = is_array($item) ? ($item['class'] ?? null) : null;

            if (! is_string($class)) {
                throw new ToolCallException("$itemPath.class is required.");
            }

            try {
                $rule = $this->rule($condition, $item, "$itemPath ($class)");
            } catch (ToolCallException $e) {
                throw $e;
            } catch (Throwable $e) {
                throw new ToolCallException("$itemPath ($class) is invalid: {$e->getMessage()}");
            }

            if (in_array($rule->uid, array_map(fn (ConditionRuleInterface $rule) => $rule->uid, $rules), true)) {
                throw new ToolCallException("$itemPath ($class) has the same uid as another rule.");
            }

            $rules[$itemPath] = $rule;
            $group->addRule($rule);
        }

        return $group;
    }

    /**
     * Builds one condition rule from input, rejecting settings, operators, and values the rule wouldn’t keep.
     *
     * @param  array<string, mixed>  $config
     */
    private function rule(ConditionInterface $condition, array $config, string $label): ConditionRuleInterface
    {
        // A rule without operators has no operator setting
        if (array_key_exists('operator', $config) && $config['operator'] === null) {
            unset($config['operator']);
        }

        $rule = $condition->createConditionRule($config);

        // Conditions only take rules they offer
        if (($this->call($condition, 'validateConditionRule', $rule) ?? false) !== true) {
            throw new ToolCallException("$label isn’t available here. Use a rule from condition_schema.");
        }

        $known = [
            ...array_keys($this->template($rule) ?? []),
            ...($rule instanceof BaseConditionRule ? array_keys($rule->getRules()) : []),
            ...($rule instanceof BaseElementSelectConditionRule ? ['elementId'] : []),
            'class',
            'uid',
        ];
        $unknown = array_diff(array_keys($config), array_diff($known, ['condition']));

        if ($unknown !== []) {
            throw new ToolCallException("$label has settings the rule doesn’t accept: ".implode(', ', $unknown).'. Copy its template from condition_schema.');
        }

        // Field rules only look up their field when they’re used, which fails for fields outside the condition’s layouts
        if ($rule instanceof FieldConditionRuleInterface) {
            $rule->getLabel();
        }

        $operators = $this->call($rule, 'operators') ?? [];

        if (isset($config['operator']) && $operators === []) {
            throw new ToolCallException("$label has no operators. Omit operator.");
        }

        if (isset($config['operator']) && ! in_array($config['operator'], $operators, true)) {
            throw new ToolCallException("$label operator “{$config['operator']}” must be one of: ".implode(', ', $operators).'.');
        }

        if ($rule instanceof BaseMultiSelectConditionRule && in_array($rule->operator, ['in', 'ni'], true)) {
            $values = array_map(strval(...), array_column($this->options($this->call($rule, 'options') ?? []), 'value'));
            $invalid = array_diff(array_map(strval(...), $rule->getValues()), $values);

            if ($invalid !== []) {
                throw new ToolCallException("$label has values that aren’t among its options: ".implode(', ', $invalid).'. Use option values, not labels.');
            }
        }

        // The operator validator reads the operator, which rules without operators never set
        if ($rule instanceof BaseConditionRule && ! $rule->validate($operators === [] ? array_values(array_diff(array_keys($rule->getRules()), ['operator'])) : null)) {
            throw new ToolCallException("$label is invalid: ".implode(' ', $rule->errors()->all()));
        }

        return $rule;
    }

    /** @return array<string, mixed>|null The rule’s description, or null when it can’t match anything on this site */
    private function describeRule(ConditionRuleInterface $rule): ?array
    {
        try {
            $label = $rule->getLabel();
        } catch (Throwable) {
            return null;
        }

        $description = [
            'class' => $rule::class,
            'label' => $label,
            'group' => $rule->getGroupLabel(),
            'kind' => match (true) {
                $rule instanceof BaseMultiSelectConditionRule => 'multiSelect',
                $rule instanceof BaseSelectConditionRule => 'select',
                $rule instanceof BaseNumberConditionRule => 'number',
                $rule instanceof BaseTextConditionRule => 'text',
                $rule instanceof BaseLightswitchConditionRule => 'lightswitch',
                $rule instanceof BaseElementSelectConditionRule => 'elementSelect',
                $rule instanceof BaseDateRangeConditionRule => 'dateRange',
                default => 'other',
            },
            'template' => $this->template($rule),
        ];

        $operators = $this->call($rule, 'operators') ?? [];

        if ($operators !== []) {
            $description['operators'] = array_map(fn (string $operator) => [
                'value' => $operator,
                'label' => $this->call($rule, 'operatorLabel', $operator) ?? $operator,
            ], $operators);
        }

        if ($rule instanceof FieldConditionRuleInterface) {
            $description['field'] = $this->call($rule, 'field')?->handle;
        }

        if ($rule instanceof BaseSelectConditionRule || $rule instanceof BaseMultiSelectConditionRule) {
            $description['options'] = $this->options($this->call($rule, 'options') ?? []);

            if ($description['options'] === []) {
                if (! array_intersect($operators, ['empty', 'notempty'])) {
                    return null;
                }

                $description['notes'] = 'Nothing to choose from on this site, so only the empty and notempty operators apply.';
            }
        } elseif ($rule instanceof BaseDateRangeConditionRule) {
            $description['rangeTypes'] = $this->options($this->call($rule, 'rangeTypeOptions') ?? []);
            $description['periodTypes'] = $this->options($this->call($rule, 'periodTypeOptions') ?? []);
            $description['notes'] = 'Pick a rangeType. today through pastYear, notempty, and empty need nothing else. range takes startDate and/or endDate (Y-m-d, both inclusive). before and after compare with a time relative to now, set by periodValue (1 or more; empty matches everything) and periodType: in the future is after 1 minutesAgo, and in the past is before 1 minutesAgo.';
        } elseif ($rule instanceof BaseElementSelectConditionRule) {
            $description['elementType'] = $this->call($rule, 'elementType');
            $description['multiple'] = $this->call($rule, 'allowMultiple') ?? false;
            $description['notes'] = 'Set elementIds to element IDs of elementType.';
        }

        return array_filter($description, fn ($value) => $value !== null);
    }

    /** @return array<string, mixed>|null The settings a rule saves, without its UID */
    private function template(ConditionRuleInterface $rule): ?array
    {
        try {
            $config = $rule->getConfig();
        } catch (Throwable) {
            return null;
        }

        unset($config['uid']);

        return $config;
    }

    /**
     * @param  array<string|int, mixed>  $options
     * @return list<array{value: mixed, label: string}>
     */
    private function options(array $options): array
    {
        $normalized = [];

        foreach ($options as $key => $option) {
            if (is_array($option)) {
                if (! array_key_exists('value', $option) && ! isset($option['label'])) {
                    continue;
                }

                $normalized[] = ['value' => $option['value'] ?? $key, 'label' => (string) ($option['label'] ?? $option['value'] ?? $key)];
            } else {
                $normalized[] = ['value' => $key, 'label' => (string) $option];
            }
        }

        return $normalized;
    }

    /** Calls a protected method, returning null when the object doesn’t define it. */
    private function call(object $object, string $method, mixed ...$args): mixed
    {
        if (! method_exists($object, $method)) {
            return null;
        }

        try {
            return new ReflectionMethod($object, $method)->invoke($object, ...$args);
        } catch (Throwable) {
            return null;
        }
    }
}
