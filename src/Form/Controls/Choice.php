<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Controls;

use CraftCms\Cms\Cp\Components\Button;
use CraftCms\Cms\Cp\Components\ButtonGroup;
use CraftCms\Cms\Cp\Components\Checkbox;
use CraftCms\Cms\Cp\Components\CheckboxGroup;
use CraftCms\Cms\Cp\Components\CheckboxIndeterminate;
use CraftCms\Cms\Cp\Components\CheckboxSelect;
use CraftCms\Cms\Cp\Components\Radio;
use CraftCms\Cms\Cp\Components\RadioGroup;
use CraftCms\Cms\Cp\Components\Select as SelectComponent;
use CraftCms\Cms\Form\ControlPayload;
use CraftCms\Cms\Form\Enums\AllOptionMode;
use CraftCms\Cms\Form\Enums\ChoicePresentation;
use CraftCms\Cms\Form\FormHtmlRenderer;
use Illuminate\Support\HtmlString;
use InvalidArgumentException;

use function CraftCms\Cms\t;

class Choice extends Control
{
    /** The token {@see self::allOption()} posts by default. */
    public const string ALL_VALUE = '*';

    /**
     * Reordering renders a flat `cp-checkbox-select` while “All” nests the
     * options inside {@see CheckboxIndeterminate} to govern them, so no single
     * markup can be both.
     */
    private const string SORTABLE_ALL_CONFLICT = 'A sortable Choice cannot also have an “All” option.';

    /** @var list<array{label: string, labelHtml?: string, icon?: string, value: bool|float|int|string, disabled?: bool, thumbnail?: array{src: string, width?: int, height?: int, aspectRatio?: string}}> */
    private array $options = [];

    private bool $multiple = false;

    private bool $sortable = false;

    private ChoicePresentation $presentation = ChoicePresentation::Select;

    private ?string $allLabel = null;

    private string $allValue = self::ALL_VALUE;

    private AllOptionMode $allMode = AllOptionMode::SingleValue;

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

    /**
     * An option may carry a `thumbnail` illustrating the choice, rendered above
     * the radio in the Radios presentation. Its `aspectRatio` maps to the CSS
     * property of the same name.
     *
     * @param  list<array{label: string, labelHtml?: string, icon?: string, value: bool|float|int|string, disabled?: bool, thumbnail?: array{src: string, width?: int, height?: int, aspectRatio?: string}}>  $options
     */
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
        if ($sortable && $this->allLabel !== null) {
            throw new InvalidArgumentException(self::SORTABLE_ALL_CONFLICT);
        }

        $this->sortable = $sortable;

        if ($sortable) {
            $this->asCheckboxes();
        }

        return $this;
    }

    public function presentation(ChoicePresentation $presentation): static
    {
        $this->presentation = $presentation;

        return $this;
    }

    /**
     * Adds an “All” checkbox above the options, showing an indeterminate state
     * while only some are on. Checkboxes presentation only.
     *
     * By default it posts `$value` alone and the options post nothing, so the
     * stored setting stays “all of them” rather than freezing today's list —
     * see {@see AllOptionMode}. Pass {@see AllOptionMode::EachValue} for a
     * plain select-all instead.
     */
    public function allOption(
        ?string $label = null,
        string $value = self::ALL_VALUE,
        AllOptionMode $mode = AllOptionMode::SingleValue,
    ): static {
        if ($this->sortable) {
            throw new InvalidArgumentException(self::SORTABLE_ALL_CONFLICT);
        }

        $this->allLabel = $label ?? t('All');
        $this->allValue = $value;
        $this->allMode = $mode;
        $this->asCheckboxes();

        return $this;
    }

    #[\Override]
    public function props(mixed $value = null): array
    {
        $props = [
            'options' => $this->options,
            'multiple' => $this->multiple,
            'presentation' => $this->presentation->value,
            ...($this->allLabel !== null ? [
                'allLabel' => $this->allLabel,
                'allValue' => $this->allValue,
                'allMode' => $this->allMode->value,
            ] : []),
        ];

        // Only emitted when set, so ordinary Choice payloads are unchanged.
        if ($this->sortable) {
            $props['sortable'] = true;
        }

        return $props;
    }

    /** Reordering and “All” are only meaningful on a multi-select checkbox list. */
    private function asCheckboxes(): void
    {
        $this->multiple = true;
        $this->presentation = ChoicePresentation::Checkboxes;
    }

    /**
     * The options a single `<select>` offers, with somewhere to represent
     * "nothing chosen" when the control permits it.
     *
     * A `<select>` with no selected option shows its first one, so an optional
     * setting that has never been set looks set — and because nothing changed,
     * saving posts nothing and it stays unset. A leading blank makes the empty
     * state visible, and re-selectable once something has been chosen.
     *
     * A required control has no valid empty state to offer, and options that
     * already carry an empty value supply their own.
     *
     * `ChoiceControl.vue`'s `selectOptions` mirrors this for the Vue renderer.
     *
     * @param  list<array<string, mixed>>  $options
     * @return list<array<string, mixed>>
     */
    private static function selectOptions(array $options, bool $required): array
    {
        if ($required) {
            return $options;
        }

        foreach ($options as $option) {
            if (($option['value'] ?? null) === '') {
                return $options;
            }
        }

        return [['label' => '', 'value' => ''], ...$options];
    }

    /** @param array<string, mixed> $attributes */
    private static function selectHtml(ControlPayload $control, mixed $value, array $attributes): string
    {
        return self::select($control, $attributes)
            ->options(self::selectOptions($control->props['options'], (bool) $attributes['required']))
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
        if ($control->props['sortable'] ?? false) {
            return self::checkboxSelectHtml($control, $value, $attributes);
        }

        $values = self::values($value);
        $name = self::multipleName($attributes['name']);
        $allLabel = $control->props['allLabel'] ?? null;
        $allValue = (string) ($control->props['allValue'] ?? self::ALL_VALUE);
        $singleValue = $allLabel !== null
            && ($control->props['allMode'] ?? null) === AllOptionMode::SingleValue->value;
        // In single-value mode “All” speaks for the options: they render checked
        // so the group reads as fully selected, but drop their name so only the
        // token posts. Not disabled — Lion skips disabled children when “All”
        // propagates, which would leave it unable to clear them again.
        $allChecked = $singleValue && in_array($allValue, $values, true);
        $checkboxes = array_map(function (array $option, int $index) use ($attributes, $name, $values, $allChecked): Checkbox {
            $optionValue = (string) $option['value'];

            return Checkbox::make()
                ->id("{$attributes['id']}-{$index}")
                ->name($allChecked ? null : $name)
                ->value($optionValue)
                ->checked($allChecked || in_array($optionValue, $values, true))
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
            // “All” owns the boxes it governs, so when it's in play the group
            // holds it alone rather than the checkboxes directly.
            ->options($allLabel !== null
                ? [self::allCheckbox($attributes, $name, $allLabel, $allValue, $singleValue, $allChecked, $checkboxes)]
                : $checkboxes)
            ->attributes(self::groupAttributes($attributes, 'group'))
            ->toHtml();
    }

    /**
     * Renders through {@see CheckboxSelect}, which owns the drag-reordering
     * wrapper.
     *
     * @param  array<string, mixed>  $attributes
     */
    private static function checkboxSelectHtml(ControlPayload $control, mixed $value, array $attributes): string
    {
        $name = $attributes['name'];
        $disabled = $name === null;
        $values = self::values($value);

        /** @var list<array{label: string, labelHtml?: string, icon?: string, value: bool|float|int|string, disabled?: bool}> $options */
        $options = $control->props['options'];

        // The value carries the display order, so selected options lead.
        if ($values !== []) {
            usort($options, fn (array $a, array $b): int => self::valueOrder($a, $values) <=> self::valueOrder($b, $values));
        }

        $checkboxes = array_map(function (array $option, int $index) use ($attributes, $name, $values, $disabled): Checkbox {
            $optionValue = (string) $option['value'];

            return Checkbox::make()
                ->id("{$attributes['id']}-{$index}")
                ->name(self::multipleName($name))
                ->value($optionValue)
                ->checked(in_array($optionValue, $values, true))
                ->disabled($disabled || ($option['disabled'] ?? false))
                ->label(self::optionLabel($option))
                ->describedBy($attributes['aria']['describedby'] ?? null)
                ->inputAttributes([
                    'aria' => ['invalid' => $attributes['aria']['invalid'] ?? null],
                ]);
        }, $options, array_keys($options));

        return CheckboxSelect::make()
            ->id($attributes['id'])
            ->name($name)
            ->options($checkboxes)
            ->sortable()
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

    /**
     * The “All” checkbox wrapping the options it governs.
     *
     * It only carries a name and value in single-value mode; as a select-all it
     * posts nothing of its own.
     *
     * @param  array<string, mixed>  $attributes
     * @param  list<Checkbox>  $checkboxes
     */
    private static function allCheckbox(
        array $attributes,
        ?string $name,
        string $label,
        string $value,
        bool $singleValue,
        bool $checked,
        array $checkboxes,
    ): CheckboxIndeterminate {
        return CheckboxIndeterminate::make()
            ->id("{$attributes['id']}-all")
            ->label($label)
            ->name($singleValue ? $name : null)
            ->value($value)
            ->checked($checked)
            ->disabled($attributes['name'] === null)
            ->children($checkboxes);
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
                ->thumbnail($option['thumbnail'] ?? null)
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
