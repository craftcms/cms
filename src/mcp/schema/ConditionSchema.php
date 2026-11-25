<?php

declare(strict_types=1);

namespace craft\mcp\schema;

use craft\base\conditions\BaseConditionRule;
use craft\base\conditions\BaseDateRangeConditionRule;
use craft\base\conditions\BaseElementSelectConditionRule;
use craft\base\conditions\BaseLightswitchConditionRule;
use craft\base\conditions\BaseMultiSelectConditionRule;
use craft\base\conditions\BaseNumberConditionRule;
use craft\base\conditions\BaseSelectConditionRule;
use craft\base\conditions\BaseTextConditionRule;
use craft\base\conditions\ConditionInterface;
use craft\base\conditions\ConditionRuleInterface;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionInterface;
use craft\elements\User;
use craft\fields\BaseRelationField;
use craft\fields\conditions\FieldConditionRuleInterface;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\models\FieldLayout;
use Mcp\Exception\ToolCallException;
use ReflectionMethod;
use Throwable;

/**
 * Describes the rules a condition accepts and builds conditions from MCP input, the same way wherever conditions are
 * configured: element source sidebars, field layout visibility, and relation field selection.
 *
 * @since 5.13.0
 */
class ConditionSchema
{
    /** The input schema of a condition config. */
    public const Schema = [
        'type' => ['object', 'null'],
        'description' => '{conditionRules: [...]}, each rule built from a condition_schema template. The class, elementType, and fieldContext that reads return, and each rule’s uid, can be sent back or left out.',
        'properties' => [
            'conditionRules' => ['type' => 'array', 'items' => ['type' => 'object', 'additionalProperties' => true]],
        ],
        'additionalProperties' => true,
    ];

    /**
     * Returns the condition Customize Sources uses for an element type’s custom sources.
     *
     * @param class-string<ElementInterface> $elementType
     */
    public function forElementSources(string $elementType): ElementConditionInterface
    {
        $condition = $elementType::createCondition();
        $condition->forProjectConfig = true;
        $condition->queryParams = ['site', 'status'];

        return $condition;
    }

    /**
     * Returns the visibility condition of a field layout’s tabs and elements, as the field layout designer builds it.
     *
     * @param class-string<ElementInterface> $elementType
     * @param FieldLayout|null $layout The layout whose fields element rules can use, or null for all of the element type’s layouts
     * @param 'element'|'user' $kind
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

        if (!$condition instanceof ElementConditionInterface) {
            throw new ToolCallException(sprintf('%s fields don’t have selection conditions.', $field::displayName()));
        }

        $condition->forProjectConfig = true;
        $condition->queryParams[] = 'site';

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

            $rules[json_encode([$description['class'], $description['template']['fieldUid'] ?? null, $description['label']])] ??= $description;
        }

        return [
            'conditionClass' => get_class($condition),
            'notes' => [
                'A condition is {conditionRules: [...]}. Copy a rule’s template, which holds every setting the rule saves, set its values from the rule’s operators and options, and add it to conditionRules. Saving a condition replaces all of its rules, so send the existing ones too. null or an empty conditionRules removes the condition.',
                'Rules match only when all of them match; conditions can’t combine rules with OR. One rule with several values matches any of them.',
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
     * @param ConditionInterface $condition A new condition from one of the for*() methods
     * @return array<string, mixed>
     */
    public function normalize(ConditionInterface $condition, mixed $input, string $path): array
    {
        $input ??= [];

        if (!is_array($input)) {
            throw new ToolCallException("$path must be an object.");
        }

        // Field settings are read in project config’s packed form
        $input = ProjectConfigHelper::unpackAssociativeArray($input);

        if (isset($input['class']) && $input['class'] !== get_class($condition)) {
            throw new ToolCallException(sprintf('%s.class must be %s, or omitted.', $path, get_class($condition)));
        }

        $rules = [];

        foreach ($input['conditionRules'] ?? [] as $i => $ruleConfig) {
            $rulePath = "$path.conditionRules[$i]";
            $class = is_array($ruleConfig) ? ($ruleConfig['class'] ?? null) : null;

            if (!is_string($class)) {
                throw new ToolCallException("$rulePath.class is required.");
            }

            try {
                $rules[] = $this->rule($condition, $ruleConfig, "$rulePath ($class)");
            } catch (ToolCallException $e) {
                throw $e;
            } catch (Throwable $e) {
                throw new ToolCallException("$rulePath ($class) is invalid: {$e->getMessage()}");
            }
        }

        $condition->setConditionRules($rules);
        $config = $condition->getConfig();
        // Craft drops rules that aren’t selectable when they’re set, and field rules for fields it can’t find when it saves them
        $kept = array_column($config['conditionRules'], 'uid');

        foreach ($rules as $i => $rule) {
            if (!in_array($rule->uid, $kept, true)) {
                throw new ToolCallException(sprintf('%s.conditionRules[%s] (%s) isn’t available here, or conflicts with another rule. Use a rule from condition_schema.', $path, $i, get_class($rule)));
            }
        }

        return $config;
    }

    /**
     * Builds one condition rule from input, rejecting settings, operators, and values the rule wouldn’t keep.
     *
     * @param array<string, mixed> $config
     */
    private function rule(ConditionInterface $condition, array $config, string $label): ConditionRuleInterface
    {
        // A rule without operators has no operator setting
        if (array_key_exists('operator', $config) && $config['operator'] === null) {
            unset($config['operator']);
        }

        $rule = $condition->createConditionRule($config);
        $known = [...array_keys($this->template($rule) ?? []), ...(method_exists($rule, 'safeAttributes') ? $rule->safeAttributes() : []), 'class', 'uid'];
        $unknown = array_diff(array_keys($config), $known);

        if ($unknown !== []) {
            throw new ToolCallException("$label has settings the rule doesn’t accept: " . implode(', ', $unknown) . '. Copy its template from condition_schema.');
        }

        // Field rules only look up their field when they’re used, which fails for fields outside the condition’s layouts
        if ($rule instanceof FieldConditionRuleInterface) {
            $rule->getLabel();
        }

        $operators = $this->call($rule, 'operators') ?? [];

        if (isset($config['operator']) && $operators === []) {
            throw new ToolCallException("$label has no operators. Omit operator.");
        }

        if (isset($config['operator']) && !in_array($config['operator'], $operators, true)) {
            throw new ToolCallException("$label operator “{$config['operator']}” must be one of: " . implode(', ', $operators) . '.');
        }

        if ($rule instanceof BaseMultiSelectConditionRule && in_array($rule->operator, ['in', 'ni'], true)) {
            $values = array_map('strval', array_column($this->options($this->call($rule, 'options') ?? []), 'value'));
            $invalid = array_diff(array_map('strval', $rule->getValues()), $values);

            if ($invalid !== []) {
                throw new ToolCallException("$label has values that aren’t among its options: " . implode(', ', $invalid) . '. Use option values, not labels.');
            }
        }

        // The operator validator reads the operator, which rules without operators never set
        if ($rule instanceof BaseConditionRule && !$rule->validate($operators === [] ? array_diff($rule->activeAttributes(), ['operator']) : null)) {
            throw new ToolCallException("$label is invalid: " . implode(' ', $rule->getErrorSummary(true)));
        }

        return $rule;
    }

    /** @return array<string, mixed>|null The rule’s description, or null when it can’t match anything on this site */
    private function describeRule(ConditionRuleInterface $rule): ?array
    {
        $description = [
            'class' => get_class($rule),
            'label' => $rule->getLabel(),
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
            $description['operators'] = array_map(fn(string $operator) => [
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
                if (!array_intersect($operators, ['empty', 'notempty'])) {
                    return null;
                }

                $description['notes'] = 'Nothing to choose from on this site, so only the empty and notempty operators apply.';
            }
        } elseif ($rule instanceof BaseDateRangeConditionRule) {
            $description['rangeTypes'] = $this->options($this->call($rule, 'rangeTypeOptions') ?? []);
            $description['periodTypes'] = $this->options($this->call($rule, 'periodTypeOptions') ?? []);
            $description['notes'] = 'Pick a rangeType. today through pastYear need nothing else. range takes startDate and/or endDate (Y-m-d, both inclusive). before and after compare with a time relative to now, set by periodValue (1 or more; empty matches everything) and periodType: in the future is after 1 minutesAgo, and in the past is before 1 minutesAgo.';
        } elseif ($rule instanceof BaseElementSelectConditionRule) {
            $description['elementType'] = $this->call($rule, 'elementType');
            $description['multiple'] = $this->call($rule, 'allowMultiple') ?? false;
            $description['notes'] = 'Set elementIds to element IDs of elementType.';
        }

        return array_filter($description, fn($value) => $value !== null);
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
     * @param array<string|int, mixed> $options
     * @return list<array{value: mixed, label: string}>
     */
    private function options(array $options): array
    {
        $normalized = [];

        foreach ($options as $key => $option) {
            if (is_array($option)) {
                if (!array_key_exists('value', $option) && !isset($option['label'])) {
                    continue;
                }

                $normalized[] = ['value' => $option['value'] ?? $key, 'label' => (string)($option['label'] ?? $option['value'] ?? $key)];
            } else {
                $normalized[] = ['value' => $key, 'label' => (string)$option];
            }
        }

        return $normalized;
    }

    /** Calls a protected method, returning null when the object doesn’t define it. */
    private function call(object $object, string $method, mixed ...$args): mixed
    {
        if (!method_exists($object, $method)) {
            return null;
        }

        try {
            return (new ReflectionMethod($object, $method))->invoke($object, ...$args);
        } catch (Throwable) {
            return null;
        }
    }
}
