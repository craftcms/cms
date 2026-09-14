<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Controls;

use CraftCms\Cms\Condition\BaseCondition;
use CraftCms\Cms\Condition\ConditionBuilderRenderer;
use CraftCms\Cms\Condition\Contracts\ConditionInterface;
use CraftCms\Cms\Form\ControlPayload;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Support\Facades\Conditions;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\Json;
use InvalidArgumentException;

class ConditionBuilder extends Control
{
    /** @var class-string<ConditionInterface>|null */
    private ?string $conditionClass = null;

    /** @var list<string> */
    private array $queryParams = [];

    private bool $forProjectConfig = false;

    /** @var list<array<string, mixed>> */
    private array $fieldLayouts = [];

    private ?string $addRuleLabel = null;

    public static function renderHtml(ControlPayload $control, mixed $value, array $attributes, FormHtmlRenderer $renderer): string
    {
        return self::builderHtml(
            is_array($value) ? $value : [],
            $control->props['conditionClass'],
            $control->props['queryParams'],
            (bool) $control->props['forProjectConfig'],
            $attributes['name'],
            $attributes['name'] === null,
            $control->props['fieldLayouts'] ?? [],
            $control->props['addRuleLabel'] ?? null,
        );
    }

    /**
     * @param  array<string, mixed>  $value
     * @param  class-string<ConditionInterface>  $conditionClass
     * @param  list<string>  $queryParams
     * @param  list<array<string, mixed>>  $fieldLayouts
     */
    public static function builderHtml(
        array $value,
        string $conditionClass,
        array $queryParams,
        bool $forProjectConfig,
        ?string $name,
        bool $disabled,
        array $fieldLayouts = [],
        ?string $addRuleLabel = null,
    ): string {
        $condition = self::condition($value, $conditionClass, $queryParams, $forProjectConfig, $fieldLayouts, $addRuleLabel);
        $condition->mainTag = 'div';
        $condition->name = $name === null ? 'condition' : self::leafName($name);
        $namespace = $name === null ? null : self::parentInputName($name);

        return InputNamespace::namespaceInputs(new ConditionBuilderRenderer($condition, ! $disabled)->render(...), $namespace);
    }

    public function component(): string
    {
        return 'craft:condition-builder';
    }

    /** @param class-string<ConditionInterface> $conditionClass */
    public function conditionClass(string $conditionClass): static
    {
        if (! is_a($conditionClass, ConditionInterface::class, true)) {
            throw new InvalidArgumentException("Condition [{$conditionClass}] must implement ".ConditionInterface::class.'.');
        }

        $this->conditionClass = $conditionClass;

        return $this;
    }

    /** @param list<string> $queryParams */
    public function queryParams(array $queryParams): static
    {
        $this->queryParams = $queryParams;

        return $this;
    }

    public function forProjectConfig(bool $forProjectConfig = true): static
    {
        $this->forProjectConfig = $forProjectConfig;

        return $this;
    }

    /**
     * Field layout configs the condition can offer field-based rules for.
     *
     * @param  list<array<string, mixed>>  $fieldLayouts
     */
    public function fieldLayouts(array $fieldLayouts): static
    {
        $this->fieldLayouts = $fieldLayouts;

        return $this;
    }

    /** Overrides the add-button label, e.g. “Add a filter”. */
    public function addRuleLabel(?string $addRuleLabel): static
    {
        $this->addRuleLabel = $addRuleLabel;

        return $this;
    }

    #[\Override]
    public function props(mixed $value = null): array
    {
        if ($this->conditionClass === null) {
            throw new InvalidArgumentException('ConditionBuilder Controls require a condition class.');
        }

        $props = [
            'conditionClass' => $this->conditionClass,
            'queryParams' => $this->queryParams,
            'forProjectConfig' => $this->forProjectConfig,
            'fieldLayouts' => $this->fieldLayouts,
        ];

        // Only emitted when set, so the condition's own default survives.
        if ($this->addRuleLabel !== null) {
            $props['addRuleLabel'] = $this->addRuleLabel;
        }

        $condition = self::condition(is_array($value) ? $value : [], $this->conditionClass, $this->queryParams, $this->forProjectConfig, $this->fieldLayouts, $this->addRuleLabel);
        $props['builder'] = Json::decode(Json::encode(app(\CraftCms\Cms\Condition\ConditionBuilder::class)->resolve($condition)));

        return $props;
    }

    /**
     * @param  array<string, mixed>  $value
     * @param  class-string<ConditionInterface>  $conditionClass
     * @param  list<string>  $queryParams
     * @param  list<array<string, mixed>>  $fieldLayouts
     */
    private static function condition(array $value, string $conditionClass, array $queryParams, bool $forProjectConfig, array $fieldLayouts, ?string $addRuleLabel): BaseCondition
    {
        $config = [
            ...$value,
            'class' => $conditionClass,
            'forProjectConfig' => $forProjectConfig,
            'conditionRules' => [],
        ];

        if ($fieldLayouts !== [] && ! isset($config['fieldLayouts'])) {
            $config['fieldLayouts'] = $fieldLayouts;
        }

        $condition = Conditions::createCondition($config);

        if (! $condition instanceof BaseCondition) {
            throw new InvalidArgumentException("Condition [{$conditionClass}] must extend ".BaseCondition::class.'.');
        }

        if ($addRuleLabel !== null) {
            $condition->addRuleLabel = $addRuleLabel;
        }

        if (property_exists($condition, 'queryParams')) {
            $condition->queryParams = array_values(array_unique([...$condition->queryParams, ...$queryParams]));
        }

        $condition->setConditionRules($value['conditionRules'] ?? []);

        return $condition;
    }
}
