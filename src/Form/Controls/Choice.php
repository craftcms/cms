<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Controls;

use CraftCms\Cms\Cp\Components\Button;
use CraftCms\Cms\Cp\Components\ButtonGroup;
use CraftCms\Cms\Cp\Components\Checkbox;
use CraftCms\Cms\Cp\Components\CheckboxGroup;
use CraftCms\Cms\Cp\Components\CheckboxSelect;
use CraftCms\Cms\Cp\Components\Radio;
use CraftCms\Cms\Cp\Components\RadioGroup;
use CraftCms\Cms\Cp\Components\Select as SelectComponent;
use CraftCms\Cms\Form\ControlPayload;
use CraftCms\Cms\Form\Enums\ChoicePresentation;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Support\Html;
use Illuminate\Support\HtmlString;

use function CraftCms\Cms\t;

class Choice extends Control
{
    /** The value posted when the “All” option is selected. */
    public const string ALL_VALUE = '*';

    /** @var list<array{label: string, labelHtml?: string, icon?: string, value: bool|float|int|string, disabled?: bool}> */
    private array $options = [];

    private bool $multiple = false;

    private bool $sortable = false;

    private bool $allowAll = false;

    private ChoicePresentation $presentation = ChoicePresentation::Select;

    public static function renderHtml(ControlPayload $control, mixed $value, array $attributes, FormHtmlRenderer $renderer): string
    {
        $multiple = (bool) ($control->props['multiple'] ?? false);
        $presentation = ChoicePresentation::from((string) ($control->props['presentation'] ?? ChoicePresentation::Select->value));

        return match ($presentation) {
            ChoicePresentation::Select => $multiple
                ? self::multipleSelectHtml($control, $value, $attributes)
                : self::selectHtml($control, $value, $attributes),
            ChoicePresentation::Checkboxes => self::checkboxesHtml($control, $value, $attributes),
            ChoicePresentation::Radios => self::radiosHtml($control, $value, $attributes),
            ChoicePresentation::Buttons => $multiple
                ? self::multipleButtonsHtml($control, $value, $attributes)
                : self::buttonsHtml($control, $value, $attributes),
        };
    }

    public function component(): string
    {
        return 'craft:choice';
    }

    /** @param list<array{label: string, labelHtml?: string, icon?: string, value: bool|float|int|string, disabled?: bool}> $options */
    public function options(array $options): static
    {
        $this->options = $options;

        return $this;
    }

    public function multiple(bool $multiple = true): static
    {
        $this->multiple = $multiple;

        if ($multiple && $this->presentation === ChoicePresentation::Select) {
            $this->presentation = ChoicePresentation::Checkboxes;
        }

        return $this;
    }

    /**
     * Lets the options be reordered, with the value stored in display order.
     * Implies a multi-select checkbox list.
     */
    public function sortable(bool $sortable = true): static
    {
        $this->sortable = $sortable;

        if ($sortable) {
            $this->asCheckboxes();
        }

        return $this;
    }

    /**
     * Adds an “All” option posting {@see self::ALL_VALUE}, which checks and
     * disables every other option. Implies a multi-select checkbox list.
     */
    public function allowAll(bool $allowAll = true): static
    {
        $this->allowAll = $allowAll;

        if ($allowAll) {
            $this->asCheckboxes();
        }

        return $this;
    }

    public function presentation(ChoicePresentation $presentation): static
    {
        $this->presentation = $presentation;

        return $this;
    }

    #[\Override]
    public function props(mixed $value = null): array
    {
        $props = [
            'options' => $this->options,
            'multiple' => $this->multiple,
            'presentation' => $this->presentation->value,
        ];

        // Only emitted when set, so ordinary Choice payloads are unchanged.
        if ($this->sortable) {
            $props['sortable'] = true;
        }

        if ($this->allowAll) {
            $props['allowAll'] = true;
        }

        return $props;
    }

    /** Sortable and All are only meaningful on a multi-select checkbox list. */
    private function asCheckboxes(): void
    {
        $this->multiple = true;
        $this->presentation = ChoicePresentation::Checkboxes;
    }

    /** @param array<string, mixed> $attributes */
    private static function selectHtml(ControlPayload $control, mixed $value, array $attributes): string
    {
        return self::select($control, $attributes)
            ->name($attributes['name'])
            ->value(self::values($value)[0] ?? null)
            ->toHtml();
    }

    /** @param array<string, mixed> $attributes */
    private static function multipleSelectHtml(ControlPayload $control, mixed $value, array $attributes): string
    {
        return self::select($control, $attributes)
            ->name(self::multipleName($attributes['name']))
            ->value(self::values($value))
            ->multiple()
            ->toHtml();
    }

    /** @param array<string, mixed> $attributes */
    private static function select(ControlPayload $control, array $attributes): SelectComponent
    {
        return SelectComponent::make()
            ->id($attributes['id'])
            ->options($control->props['options'])
            ->disabled($attributes['name'] === null)
            ->required($attributes['required'])
            ->describedBy($attributes['aria']['describedby'] ?? null)
            ->selectAttributes([
                'aria' => ['invalid' => $attributes['aria']['invalid'] ?? null],
            ]);
    }

    /** @param array<string, mixed> $attributes */
    private static function checkboxesHtml(
        ControlPayload $control,
        mixed $value,
        array $attributes,
    ): string {
        if (($control->props['sortable'] ?? false) || ($control->props['allowAll'] ?? false)) {
            return self::checkboxSelectHtml($control, $value, $attributes);
        }

        $values = self::values($value);
        $name = self::multipleName($attributes['name']);
        $checkboxes = array_map(function (array $option, int $index) use ($attributes, $name, $values): Checkbox {
            $optionValue = (string) $option['value'];

            return Checkbox::make()
                ->id("{$attributes['id']}-{$index}")
                ->name($name)
                ->value($optionValue)
                ->checked(in_array($optionValue, $values, true))
                ->disabled($attributes['name'] === null || ($option['disabled'] ?? false))
                ->label(self::optionLabel($option))
                ->describedBy($attributes['aria']['describedby'] ?? null)
                ->inputAttributes([
                    'aria' => ['invalid' => $attributes['aria']['invalid'] ?? null],
                ]);
        }, $control->props['options'], array_keys($control->props['options']));

        return CheckboxGroup::make()
            ->id($attributes['id'])
            ->name($attributes['name'])
            ->options($checkboxes)
            ->attributes(self::groupAttributes($attributes, 'group'))
            ->toHtml();
    }

    /**
     * Renders through {@see CheckboxSelect}, which owns the “All” checkbox and
     * the drag-reordering wrapper.
     *
     * @param  array<string, mixed>  $attributes
     */
    private static function checkboxSelectHtml(ControlPayload $control, mixed $value, array $attributes): string
    {
        $sortable = (bool) ($control->props['sortable'] ?? false);
        $allowAll = (bool) ($control->props['allowAll'] ?? false);
        $name = $attributes['name'];
        $disabled = $name === null;
        $allChecked = $allowAll && $value === self::ALL_VALUE;
        $values = $allChecked ? [] : self::values($value);

        /** @var list<array{label: string, labelHtml?: string, icon?: string, value: bool|float|int|string, disabled?: bool}> $options */
        $options = $control->props['options'];

        // The value carries the display order, so selected options lead.
        if ($sortable && $values !== []) {
            usort($options, fn (array $a, array $b): int => self::valueOrder($a, $values) <=> self::valueOrder($b, $values));
        }

        $checkboxes = array_map(function (array $option, int $index) use ($attributes, $name, $values, $allChecked, $disabled): Checkbox {
            $optionValue = (string) $option['value'];

            return Checkbox::make()
                ->id("{$attributes['id']}-{$index}")
                ->name(self::multipleName($name))
                ->value($optionValue)
                ->checked($allChecked || in_array($optionValue, $values, true))
                ->disabled($disabled || $allChecked || ($option['disabled'] ?? false))
                ->label(self::optionLabel($option))
                ->describedBy($attributes['aria']['describedby'] ?? null)
                ->inputAttributes([
                    'aria' => ['invalid' => $attributes['aria']['invalid'] ?? null],
                ]);
        }, $options, array_keys($options));

        return CheckboxSelect::make()
            ->id($attributes['id'])
            // The “All” checkbox posts the bare name, so the group must not
            // also emit its always-post hidden input.
            ->name($allowAll ? null : $name)
            ->allCheckbox($allowAll ? self::allCheckbox($attributes, $allChecked, $disabled) : null)
            ->options($checkboxes)
            ->sortable($sortable)
            ->disabled($disabled)
            ->attributes(self::groupAttributes($attributes, 'group'))
            ->toHtml();
    }

    /**
     * @param  array{value: bool|float|int|string}  $option
     * @param  list<string>  $values
     */
    private static function valueOrder(array $option, array $values): int
    {
        $index = array_search((string) $option['value'], $values, true);

        return $index === false ? PHP_INT_MAX : $index;
    }

    /** @param array<string, mixed> $attributes */
    private static function allCheckbox(array $attributes, bool $checked, bool $disabled): Checkbox
    {
        return Checkbox::make()
            ->id("{$attributes['id']}-all")
            ->name($attributes['name'])
            ->value(self::ALL_VALUE)
            ->checked($checked)
            ->disabled($disabled)
            ->label(new HtmlString('<b>'.Html::encode(t('All')).'</b>'))
            ->describedBy($attributes['aria']['describedby'] ?? null)
            ->inputAttributes(['class' => ['cp-checkbox-select__item', 'all']]);
    }

    /** @param array<string, mixed> $attributes */
    private static function radiosHtml(ControlPayload $control, mixed $value, array $attributes): string
    {
        $values = self::values($value);
        $radios = array_map(function (array $option, int $index) use ($attributes, $values): Radio {
            $optionValue = (string) $option['value'];

            return Radio::make()
                ->id("{$attributes['id']}-{$index}")
                ->name($attributes['name'])
                ->value($optionValue)
                ->checked(in_array($optionValue, $values, true))
                ->disabled($attributes['name'] === null || ($option['disabled'] ?? false))
                ->label(self::optionLabel($option))
                ->describedBy($attributes['aria']['describedby'] ?? null)
                ->inputAttributes([
                    'required' => $attributes['required'],
                    'aria' => ['invalid' => $attributes['aria']['invalid'] ?? null],
                ]);
        }, $control->props['options'], array_keys($control->props['options']));

        return RadioGroup::make()
            ->id($attributes['id'])
            ->options($radios)
            ->attributes(self::groupAttributes($attributes, 'radiogroup'))
            ->toHtml();
    }

    /** @param array<string, mixed> $attributes */
    private static function buttonsHtml(ControlPayload $control, mixed $value, array $attributes): string
    {
        $values = self::values($value);

        return ButtonGroup::make()
            ->id($attributes['id'])
            ->name($attributes['name'])
            ->value($values[0] ?? null)
            ->buttons(self::buttons($control, $values, $attributes))
            ->attributes(self::buttonGroupAttributes($attributes))
            ->toHtml();
    }

    /** @param array<string, mixed> $attributes */
    private static function multipleButtonsHtml(ControlPayload $control, mixed $value, array $attributes): string
    {
        $values = self::values($value);

        return ButtonGroup::make()
            ->id($attributes['id'])
            ->name($attributes['name'])
            ->multiple()
            ->buttons(self::buttons($control, $values, $attributes))
            ->attributes(self::buttonGroupAttributes($attributes))
            ->toHtml();
    }

    /**
     * @param  list<string>  $values
     * @param  array<string, mixed>  $attributes
     * @return list<Button>
     */
    private static function buttons(ControlPayload $control, array $values, array $attributes): array
    {
        return array_map(function (array $option) use ($attributes, $values): Button {
            $optionValue = (string) $option['value'];

            $icon = $option['icon'] ?? null;

            return Button::make()
                ->icon($icon)
                // An icon-only button keeps its light DOM empty, which is what
                // `<craft-button>` keys its square treatment on. The option's
                // label becomes the button's accessible name instead.
                ->label($icon !== null ? null : self::optionLabel($option))
                ->attributes($icon !== null ? ['aria' => ['label' => $option['label']]] : [])
                ->value($optionValue)
                ->active(in_array($optionValue, $values, true))
                ->disabled($attributes['name'] === null || ($option['disabled'] ?? false));
        }, $control->props['options']);
    }

    /** @return list<string> */
    private static function values(mixed $value): array
    {
        return array_map(strval(...), is_array($value) ? $value : [$value]);
    }

    private static function multipleName(?string $name): ?string
    {
        return $name === null ? null : "{$name}[]";
    }

    /** @param array{label: string, labelHtml?: string} $option */
    private static function optionLabel(array $option): string|HtmlString
    {
        return isset($option['labelHtml'])
            ? new HtmlString($option['labelHtml'])
            : $option['label'];
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private static function groupAttributes(array $attributes, string $role): array
    {
        return [
            'role' => $role,
            'disabled' => $attributes['name'] === null,
            'required' => $attributes['required'],
            'aria' => [
                'invalid' => $attributes['aria']['invalid'] ?? null,
                'describedby' => $attributes['aria']['describedby'] ?? null,
                'required' => $attributes['required'] ? 'true' : null,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private static function buttonGroupAttributes(array $attributes): array
    {
        return [
            'aria' => [
                'invalid' => $attributes['aria']['invalid'] ?? null,
                'describedby' => $attributes['aria']['describedby'] ?? null,
                'required' => $attributes['required'] ? 'true' : null,
            ],
        ];
    }
}
