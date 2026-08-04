<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use CraftCms\Cms\Cp\Concerns\HasDisabled;
use CraftCms\Cms\Cp\Concerns\HasId;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Html;

/** PHP counterpart to the `<craft-select>` web component. */
class Select extends ViewComponent
{
    use HasDisabled;
    use HasId;

    protected ?string $name = null;

    /** @var string|int|float|bool|list<string|int|float|bool>|null */
    protected string|int|float|bool|array|null $value = null;

    /** @var list<array{label: string, value: string|int|float|bool, disabled?: bool}> */
    protected array $options = [];

    protected bool $multiple = false;

    protected ?string $label = null;

    protected bool $labelSrOnly = false;

    protected bool $required = false;

    protected bool $small = false;

    protected ?string $describedBy = null;

    /** @var array<string, mixed> */
    protected array $selectAttributes = [];

    protected function tagName(): string
    {
        return 'craft-select';
    }

    public function name(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /** @param string|int|float|bool|list<string|int|float|bool>|null $value */
    public function value(string|int|float|bool|array|null $value): static
    {
        $this->value = $value;

        return $this;
    }

    /** @param list<array{label: string, value: string|int|float|bool, disabled?: bool}> $options */
    public function options(array $options): static
    {
        $this->options = $options;

        return $this;
    }

    public function label(?string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function multiple(bool $multiple = true): static
    {
        $this->multiple = $multiple;

        return $this;
    }

    public function labelSrOnly(bool $labelSrOnly = true): static
    {
        $this->labelSrOnly = $labelSrOnly;

        return $this;
    }

    public function required(bool $required = true): static
    {
        $this->required = $required;

        return $this;
    }

    public function small(bool $small = true): static
    {
        $this->small = $small;

        return $this;
    }

    public function describedBy(?string $describedBy): static
    {
        $this->describedBy = $describedBy;

        return $this;
    }

    /** @param array<string, mixed> $attributes */
    public function selectAttributes(array $attributes): static
    {
        $this->selectAttributes = Arr::merge(
            static::normalizeClasses($this->selectAttributes),
            static::normalizeClasses($attributes),
        );

        return $this;
    }

    #[\Override]
    protected function hostAttributes(): array
    {
        return [
            'small' => $this->small,
            'label' => $this->label,
            'label-sr-only' => $this->labelSrOnly,
            'name' => $this->name,
            'disabled' => $this->isDisabled(),
            'required' => $this->required,
        ];
    }

    #[\Override]
    protected function renderSlots(): string
    {
        $values = array_map(strval(...), is_array($this->value) ? $this->value : [$this->value]);
        $options = implode('', array_map(
            fn (array $option): string => Html::tag('option', Html::encode($option['label']), [
                'value' => (string) $option['value'],
                'selected' => in_array((string) $option['value'], $values, true),
                'disabled' => $option['disabled'] ?? false,
            ]),
            $this->options,
        ));

        return Html::tag('select', $options, Arr::merge([
            'slot' => 'input',
            'id' => $this->getId(),
            'name' => $this->name,
            'multiple' => $this->multiple,
            'disabled' => $this->isDisabled(),
            'required' => $this->required,
            'aria' => ['describedby' => $this->describedBy],
        ], $this->selectAttributes)).parent::renderSlots();
    }
}
