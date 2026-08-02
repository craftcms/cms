<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use Closure;
use CraftCms\Cms\Cp\Concerns\HasDisabled;
use CraftCms\Cms\Cp\Concerns\HasId;
use CraftCms\Cms\Cp\FormDefinitions\Contracts\FormElement;
use CraftCms\Cms\Cp\FormDefinitions\Data\FormElementData;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Html;

class Select extends ViewComponent implements FormElement
{
    use HasDisabled;
    use HasId;

    protected string|Closure|null $name = null;

    /** @var array<array-key, mixed>|Closure */
    protected array|Closure $options = [];

    protected string|int|float|bool|Closure|null $value = null;

    protected bool|Closure $small = false;

    protected bool|Closure $autofocus = false;

    protected string|bool|Closure|null $autocomplete = null;

    protected string|Closure|null $labelledBy = null;

    protected string|Closure|null $describedBy = null;

    /** @var array<string, mixed> */
    protected array $inputAttributes = [];

    /** @var array<string, mixed> */
    protected array $formElementAttributes = [];

    public static function formElementType(): string
    {
        return 'craft:select-input';
    }

    public static function isFormElementContainer(): bool
    {
        return false;
    }

    protected function tagName(): string
    {
        return 'craft-select';
    }

    public function name(string|Closure|null $name): static
    {
        $this->trackConfiguration('name');
        $this->name = $name;

        return $this;
    }

    /** @param array<array-key, mixed>|Closure $options */
    public function options(array|Closure $options): static
    {
        $this->trackConfiguration('options');
        $this->options = $options;

        return $this;
    }

    public function value(string|int|float|bool|Closure|null $value): static
    {
        $this->trackConfiguration('value');
        $this->value = $value;

        return $this;
    }

    public function small(bool|Closure $small = true): static
    {
        $this->trackConfiguration('small');
        $this->small = $small;

        return $this;
    }

    public function autofocus(bool|Closure $autofocus = true): static
    {
        $this->trackConfiguration('autofocus');
        $this->autofocus = $autofocus;

        return $this;
    }

    public function autocomplete(string|bool|Closure|null $autocomplete): static
    {
        $this->trackConfiguration('autocomplete');
        $this->autocomplete = $autocomplete;

        return $this;
    }

    public function labelledBy(string|Closure|null $labelledBy): static
    {
        $this->trackConfiguration('labelledBy');
        $this->labelledBy = $labelledBy;

        return $this;
    }

    public function describedBy(string|Closure|null $describedBy): static
    {
        $this->trackConfiguration('describedBy');
        $this->describedBy = $describedBy;

        return $this;
    }

    /** @param array<string, mixed> $attributes */
    public function inputAttributes(array $attributes): static
    {
        $this->trackConfiguration('inputAttributes');
        $this->inputAttributes = Arr::merge(
            static::normalizeClasses($this->inputAttributes),
            static::normalizeClasses($attributes),
        );

        return $this;
    }

    /** @param array<string, mixed> $attributes */
    #[\Override]
    public function attributes(array $attributes): static
    {
        $this->formElementAttributes = [...$this->formElementAttributes, ...$attributes];

        return parent::attributes($attributes);
    }

    public function toFormElementData(): FormElementData
    {
        $this->rejectConfiguredOptions([
            'value',
            'small',
            'autofocus',
            'autocomplete',
            'labelledBy',
            'describedBy',
            'inputAttributes',
            'disabled',
            'id',
            'slot',
        ], 'Form Definition');

        $name = $this->portableText('name', $this->name);

        if ($name === null) {
            $this->unsupportedOutputOption('name', 'Form Definition');
        }

        $options = $this->evaluate($this->options);

        if (! is_array($options) || ! array_is_list($options)) {
            $this->unsupportedOutputOption('options', 'Form Definition');
        }

        $attributes = $this->formElementAttributes;

        foreach (array_keys($attributes) as $attribute) {
            if (in_array(strtolower((string) $attribute), [
                'aria-describedby',
                'aria-labelledby',
                'disabled',
                'id',
                'name',
                'readonly',
                'slot',
                'value',
            ], true)) {
                $this->unsupportedOutputOption("attributes.{$attribute}", 'Form Definition');
            }
        }

        return new FormElementData(
            type: static::formElementType(),
            name: $name,
            props: ['options' => $options],
            attributes: $attributes === [] ? null : $attributes,
        );
    }

    #[\Override]
    protected function hostAttributes(): array
    {
        $value = $this->evaluate($this->value);

        return [
            'name' => $this->evaluate($this->name),
            'model-value' => $value === null ? '' : (string) $value,
            'disabled' => $this->isDisabled(),
            'small' => (bool) $this->evaluate($this->small),
            'class' => ['select', 'cp-select'],
        ];
    }

    #[\Override]
    protected function renderSlots(): string
    {
        $options = $this->evaluate($this->options);

        if (! is_array($options)) {
            $this->unsupportedOutputOption('options', 'HTML');
        }

        $attributes = Arr::merge([
            'slot' => 'input',
            'id' => $this->getId(),
            'class' => ['cp-form-control', 'cp-form-control--select'],
            'name' => $this->evaluate($this->name),
            'autocomplete' => $this->evaluatedAutocomplete(),
            'autofocus' => (bool) $this->evaluate($this->autofocus) && ! request()->isMobileBrowser(true),
            'disabled' => $this->isDisabled(),
            'aria' => [
                'labelledby' => $this->evaluate($this->labelledBy),
                'describedby' => $this->evaluate($this->describedBy),
            ],
        ], $this->inputAttributes);

        return Html::tag('select', $this->optionsHtml($options), $attributes).parent::renderSlots();
    }

    /** @param array<array-key, mixed> $options */
    private function optionsHtml(array $options): string
    {
        $html = '';

        foreach ($options as $key => $option) {
            if (is_array($option) && ($option['type'] ?? null) === 'optgroup') {
                $children = $option['options'] ?? [];

                if (! is_array($children)) {
                    $this->unsupportedOutputOption('options', 'HTML');
                }

                $html .= Html::tag('optgroup', $this->optionsHtml($children), [
                    'label' => $option['label'] ?? '',
                    'disabled' => $option['disabled'] ?? false,
                ]);

                continue;
            }

            if (is_array($option) && array_key_exists('optgroup', $option)) {
                $html .= Html::tag('optgroup', '', ['label' => $option['optgroup']]);

                continue;
            }

            $config = is_array($option) ? $option : ['label' => $option, 'value' => $key];
            $optionValue = array_key_exists('value', $config) ? $config['value'] : $key;
            $value = $this->evaluate($this->value);
            $html .= Html::tag('option', Html::encode((string) ($config['label'] ?? $option)), [
                'value' => $optionValue ?? '',
                'selected' => (string) ($optionValue ?? '') === (string) ($value ?? ''),
                'disabled' => $config['disabled'] ?? false,
                'hidden' => $config['hidden'] ?? false,
                'data' => $config['data'] ?? [],
            ]);
        }

        return $html;
    }

    private function evaluatedAutocomplete(): ?string
    {
        $autocomplete = $this->evaluate($this->autocomplete);

        if (is_bool($autocomplete)) {
            return $autocomplete ? 'on' : 'off';
        }

        return $autocomplete;
    }
}
