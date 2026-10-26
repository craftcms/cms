<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\ComponentKinds;

use CraftCms\Cms\Condition\Contracts\ConditionInterface;
use CraftCms\Cms\Condition\Contracts\ConditionRuleInterface;
use CraftCms\Cms\Mcp\Schema\SettingsSchema;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Conditions;
use CraftCms\Cms\Support\Json;
use Mcp\Exception\ToolCallException;

/**
 * The rules a condition offers.
 *
 * @since 6.0.0
 */
readonly class ConditionRuleKind implements ComponentKind
{
    public function __construct(private SettingsSchema $settingsSchema) {}

    public static function handle(): string
    {
        return 'condition-rule';
    }

    public function description(): string
    {
        return 'Condition rules, for a condition class that a settings schema names.';
    }

    public function usage(): string
    {
        return 'Conditions list rules in conditionRules.rules. Start each rule from the type’s config, give it a uid, and add settings from component-types.get, such as its operator and values.';
    }

    public function scope(): ?string
    {
        return 'The condition class, which settings schemas name.';
    }

    public function types(?string $scope): array
    {
        return collect($this->rules($scope))
            ->map(static fn (array $rule, string $id): array => [
                'type' => $id,
                'name' => $rule['rule']->getLabel(),
                ...($rule['rule']->getGroupLabel() !== null ? ['group' => $rule['rule']->getGroupLabel()] : []),
                'config' => $rule['config'],
            ])
            ->values()
            ->all();
    }

    public function describe(string $type, ?string $scope): array
    {
        $rule = $this->rules($scope)[$type]['rule'];

        return ['settingsSchema' => $this->settingsSchema->fromUi($rule->getUi(), $rule->getConfig())];
    }

    /**
     * Returns the rules a condition offers, keyed by an identifier that tells apart rules of the same class,
     * such as rules for different custom fields.
     *
     * @return array<string, array{rule: ConditionRuleInterface, config: array<string, mixed>}>
     */
    private function rules(?string $scope): array
    {
        if ($scope === null || ! is_subclass_of($scope, ConditionInterface::class)) {
            throw new ToolCallException('Provide scope: the condition class, which settings schemas name.');
        }

        $condition = Conditions::createCondition(['class' => $scope, 'forProjectConfig' => true]);
        $rules = [];

        foreach ($condition->getSelectableConditionRules() as $key => $rule) {
            $config = str_starts_with($key, '{') ? Json::decode($key) : ['class' => $key];
            $extra = array_filter(Arr::except($config, ['class']), is_scalar(...));
            $id = $extra === []
                ? $config['class']
                : sprintf('%s(%s)', $config['class'], implode(', ', array_map(static fn (string $name, mixed $value): string => "$name: $value", array_keys($extra), $extra)));

            $rules[$id] = ['rule' => $rule, 'config' => $config];
        }

        return $rules;
    }
}
