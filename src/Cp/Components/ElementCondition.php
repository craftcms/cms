<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use Closure;
use CraftCms\Cms\Condition\Contracts\ConditionInterface;
use CraftCms\Cms\Cp\FormDefinitions\Contracts\FormElement;
use CraftCms\Cms\Cp\FormDefinitions\Data\FormElementData;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Html;
use Override;

class ElementCondition extends ViewComponent implements FormElement
{
    protected string|Closure|null $name = null;

    protected string|Closure|null $conditionClass = null;

    /** @var array<string, mixed>|Closure|null */
    protected array|Closure|null $builderConfig = null;

    protected bool|Closure $sortable = true;

    protected string|Closure|null $addRuleLabel = null;

    protected ConditionInterface|Closure|null $condition = null;

    protected bool|Closure $readOnly = false;

    /** @var array<string, mixed> */
    protected array $formElementAttributes = [];

    public static function formElementType(): string
    {
        return 'craft:element-condition-input';
    }

    public static function isFormElementContainer(): bool
    {
        return false;
    }

    #[Override]
    protected function tagName(): string
    {
        return 'craft-element-condition';
    }

    #[Override]
    protected function hostAttributes(): array
    {
        return [
            'readonly' => $this->resolvedBool('readOnly', $this->readOnly, 'HTML'),
        ];
    }

    public function name(string|Closure|null $name): static
    {
        $this->trackConfiguration('name');
        $this->name = $name;

        return $this;
    }

    /** @param class-string<ConditionInterface>|Closure|null $conditionClass */
    public function conditionClass(string|Closure|null $conditionClass): static
    {
        $this->trackConfiguration('conditionClass');
        $this->conditionClass = $conditionClass;

        return $this;
    }

    /** @param array<string, mixed>|Closure $builderConfig */
    public function builderConfig(array|Closure $builderConfig): static
    {
        $this->trackConfiguration('builderConfig');
        $this->builderConfig = $builderConfig;

        return $this;
    }

    public function sortable(bool|Closure $sortable = true): static
    {
        $this->trackConfiguration('sortable');
        $this->sortable = $sortable;

        return $this;
    }

    public function addRuleLabel(string|Closure|null $addRuleLabel): static
    {
        $this->trackConfiguration('addRuleLabel');
        $this->addRuleLabel = $addRuleLabel;

        return $this;
    }

    public function condition(ConditionInterface|Closure|null $condition): static
    {
        $this->trackConfiguration('condition');
        $this->condition = $condition;

        return $this;
    }

    public function readOnly(bool|Closure $readOnly = true): static
    {
        $this->trackConfiguration('readOnly');
        $this->readOnly = $readOnly;

        return $this;
    }

    /** @param array<string, mixed> $attributes */
    #[Override]
    public function attributes(array $attributes): static
    {
        $this->formElementAttributes = [...$this->formElementAttributes, ...$attributes];

        return parent::attributes($attributes);
    }

    public function toFormElementData(): FormElementData
    {
        $this->rejectConfiguredOptions(['condition', 'readOnly', 'slot'], 'Form Definition');

        $name = $this->portableText('name', $this->name);
        $conditionClass = $this->portableText('conditionClass', $this->conditionClass);

        if ($name === null) {
            $this->unsupportedOutputOption('name', 'Form Definition');
        }

        if ($conditionClass === null || ! is_subclass_of($conditionClass, ConditionInterface::class)) {
            $this->unsupportedOutputOption('conditionClass', 'Form Definition');
        }

        $builderConfig = $this->resolvedBuilderConfig('Form Definition');
        $props = array_filter([
            'conditionClass' => $conditionClass,
            'builderConfig' => $builderConfig,
            'sortable' => $this->resolvedBool('sortable', $this->sortable, 'Form Definition'),
            'addRuleLabel' => $this->resolvedText('addRuleLabel', $this->addRuleLabel, 'Form Definition'),
        ], fn (mixed $value): bool => $value !== null);

        $this->validateAttributes();

        return new FormElementData(
            type: static::formElementType(),
            name: $name,
            props: $props,
            attributes: $this->formElementAttributes === [] ? null : $this->formElementAttributes,
        );
    }

    #[Override]
    protected function renderMarkup(): string
    {
        $condition = $this->evaluate($this->condition);

        if (! $condition instanceof ConditionInterface) {
            $this->unsupportedOutputOption('condition', 'HTML');
        }

        $condition = clone $condition;
        $name = $this->resolvedText('name', $this->name, 'HTML');

        if ($name === null) {
            $this->unsupportedOutputOption('name', 'HTML');
        }

        $conditionClass = $this->resolvedText('conditionClass', $this->conditionClass, 'HTML');

        if ($conditionClass !== null && $conditionClass !== $condition::class) {
            $this->unsupportedOutputOption('conditionClass', 'HTML');
        }

        if ($this->optionWasConfigured('builderConfig') && $this->resolvedBuilderConfig('HTML') !== $condition->getBuilderConfig()) {
            $this->unsupportedOutputOption('builderConfig', 'HTML');
        }

        $condition->name = $name;
        $condition->mainTag = 'div';
        $condition->forProjectConfig = true;

        if ($this->optionWasConfigured('sortable')) {
            $condition->sortable = $this->resolvedBool('sortable', $this->sortable, 'HTML');
        }

        if ($this->optionWasConfigured('addRuleLabel')) {
            $condition->addRuleLabel = $this->resolvedText('addRuleLabel', $this->addRuleLabel, 'HTML');
        }

        $attributes = Arr::merge(
            static::normalizeClasses($this->renderedAttributes()),
            [
                'id' => $condition->id,
                'class' => ['condition-container'],
            ],
        );

        return Html::tag($this->tagName(), $condition->getBuilderInnerHtml(), $attributes);
    }

    /** @return array<string, mixed> */
    private function resolvedBuilderConfig(string $output): array
    {
        $config = $this->evaluate($this->builderConfig) ?? [];

        if (! is_array($config) || array_is_list($config) && $config !== []) {
            $this->unsupportedOutputOption('builderConfig', $output);
        }

        foreach ($config as $key => $value) {
            $this->validateJsonValue($value, "builderConfig.{$key}", $output);
        }

        return $config;
    }

    private function validateJsonValue(mixed $value, string $option, string $output): void
    {
        if ($value === null || is_string($value) || is_int($value) || is_bool($value)) {
            return;
        }

        if (is_float($value)) {
            if (! is_finite($value)) {
                $this->unsupportedOutputOption($option, $output);
            }

            return;
        }

        if (! is_array($value)) {
            $this->unsupportedOutputOption($option, $output);
        }

        foreach ($value as $key => $item) {
            $this->validateJsonValue($item, "{$option}.{$key}", $output);
        }
    }

    private function resolvedText(string $option, string|Closure|null $value, string $output): ?string
    {
        $value = $this->evaluate($value);

        if ($value !== null && ! is_string($value)) {
            $this->unsupportedOutputOption($option, $output);
        }

        return $value;
    }

    private function resolvedBool(string $option, bool|Closure $value, string $output): bool
    {
        $value = $this->evaluate($value);

        if (! is_bool($value)) {
            $this->unsupportedOutputOption($option, $output);
        }

        return $value;
    }

    private function validateAttributes(): void
    {
        foreach (array_keys($this->formElementAttributes) as $attribute) {
            if (in_array(strtolower((string) $attribute), [
                'aria-describedby',
                'aria-disabled',
                'aria-labelledby',
                'disabled',
                'id',
                'name',
                'readonly',
                'required',
                'slot',
                'value',
            ], true)) {
                $this->unsupportedOutputOption("attributes.{$attribute}", 'Form Definition');
            }
        }

        $aria = $this->formElementAttributes['aria'] ?? null;

        if (! is_array($aria)) {
            return;
        }

        foreach (['describedby', 'disabled', 'labelledby'] as $attribute) {
            if (array_key_exists($attribute, $aria)) {
                $this->unsupportedOutputOption("attributes.aria-{$attribute}", 'Form Definition');
            }
        }
    }
}
