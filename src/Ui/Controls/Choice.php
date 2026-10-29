<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ui\Controls;

use CraftCms\Cms\Cp\Components\Button;
use CraftCms\Cms\Cp\Components\ButtonGroup;
use CraftCms\Cms\Cp\Components\Checkbox;
use CraftCms\Cms\Cp\Components\CheckboxGroup;
use CraftCms\Cms\Cp\Components\CheckboxSelect;
use CraftCms\Cms\Cp\Components\Icon;
use CraftCms\Cms\Cp\Components\Radio;
use CraftCms\Cms\Cp\Components\RadioGroup;
use CraftCms\Cms\Cp\Components\Select as SelectComponent;
use CraftCms\Cms\Cp\Enums\CheckboxSelectPresentation;
use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Ui\ControlPayload;
use CraftCms\Cms\Ui\Enums\AllOptionMode;
use CraftCms\Cms\Ui\Enums\ChoicePresentation;
use CraftCms\Cms\Ui\UiHtmlRenderer;
use Illuminate\Support\HtmlString;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
class Choice extends Control
{
    /** The token {@see self::allOption()} posts by default. */
    public const string ALL_VALUE = '*';

    /** @var list<array{label: string, labelHtml?: string, icon?: string, color?: string, custom?: bool, value: bool|float|int|string, disabled?: bool, thumbnail?: array{src: string, width?: int, height?: int, aspectRatio?: string}}> */
    private array $options = [];

    private bool $multiple = false;

    private bool $sortable = false;

    private bool $iconsOnly = true;

    private bool $allowCustom = false;

    private ChoicePresentation $presentation = ChoicePresentation::Select;

    /** Label for the leading blank option, or `false` when there shouldn't be one. */
    private string|false|null $placeholder = null;

    private ?string $allLabel = null;

    private string $allValue = self::ALL_VALUE;

    private AllOptionMode $allMode = AllOptionMode::SingleValue;

    public static function renderHtml(ControlPayload $control, mixed $value, array $attributes, UiHtmlRenderer $renderer): string
    {
        $multiple = (bool) ($control->props['multiple'] ?? false);
        $presentation = ChoicePresentation::from((string) ($control->props['presentation'] ?? ChoicePresentation::Select->value));

        return match ($presentation) {
            ChoicePresentation::Select => $multiple
                ? self::multipleSelectHtml($control, $value, $attributes)
                : self::selectHtml($control, $value, $attributes),
            ChoicePresentation::Checkboxes => self::checkboxesHtml($control, $value, $attributes),
            ChoicePresentation::AddMenu => self::checkboxSelectHtml($control, $value, $attributes),
            ChoicePresentation::Radios => self::radiosHtml($control, $value, $attributes),
            ChoicePresentation::Buttons => $multiple
                ? self::multipleButtonsHtml($control, $value, $attributes)
                : self::radiosHtml($control, $value, $attributes, buttons: true),
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
     * `group` heads a run of consecutive options sharing it, when presented as
     * a select; other presentations ignore it.
     *
     * `icon` and `color` are shown before a checkbox or radio label: the icon tinted
     * with the color, or a color swatch when there's no icon.
     *
     * @param  list<array{label: string, labelHtml?: string, icon?: string, color?: string, custom?: bool, value: bool|float|int|string, disabled?: bool, group?: string|null, thumbnail?: array{src: string, width?: int, height?: int, aspectRatio?: string}}>  $options
     */
    public function options(array $options): static
    {
        $this->options = array_values($options);

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
     * Implies a multi-select checkbox list, unless it's already presented with
     * an “Add item” menu.
     */
    /**
     * Whether a button with an icon shows only the icon, named by its label.
     * Without it, the icon sits beside the label. Buttons presentation only.
     */
    public function iconsOnly(bool $iconsOnly = true): static
    {
        $this->iconsOnly = $iconsOnly;

        return $this;
    }

    /**
     * Whether people can add their own options alongside the given ones.
     * Options marked `custom` are values added that way. Checkboxes and radios
     * presentations only.
     */
    public function allowCustom(bool $allowCustom = true): static
    {
        $this->allowCustom = $allowCustom;

        return $this;
    }

    public function sortable(bool $sortable = true): static
    {
        $this->sortable = $sortable;

        if ($sortable) {
            $this->asCheckboxes();
        }

        return $this;
    }

    /** {@see ChoicePresentation::AddMenu} implies a multi-select. */
    public function presentation(ChoicePresentation $presentation): static
    {
        $this->presentation = $presentation;

        if ($presentation === ChoicePresentation::AddMenu) {
            $this->multiple = true;
        }

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
    /**
     * Labels the leading blank option a single select shows while nothing is
     * chosen. Defaults to “Select…”; pass a string to say something better.
     *
     * The blank option is what makes the empty state visible, and re-selectable
     * once something has been chosen. Without this call it still renders, just
     * unlabelled.
     */
    public function placeholder(?string $label = null): static
    {
        $this->placeholder = $label ?? t('Select…');

        return $this;
    }

    /**
     * Drops the leading blank option, for a setting with no valid empty state.
     *
     * A required control already omits it — this is for one that always holds a
     * value without being marked required, so nothing can clear it.
     */
    public function withoutPlaceholder(): static
    {
        $this->placeholder = false;

        return $this;
    }

    public function allOption(
        ?string $label = null,
        string $value = self::ALL_VALUE,
        AllOptionMode $mode = AllOptionMode::SingleValue,
    ): static {
        $this->allLabel = $label ?? t('All');
        $this->allValue = $value;
        $this->allMode = $mode;
        $this->asCheckboxes();

        return $this;
    }

    #[\Override]
    public function inputSchema(): array
    {
        $choices = self::optionsSchema([
            ...$this->options,
            ...($this->allLabel !== null ? [['label' => $this->allLabel, 'value' => $this->allValue]] : []),
        ]);
        $schema = $choices !== [] ? ['oneOf' => $choices] : [];

        return $this->multiple ? ['type' => 'array', 'items' => $schema ?: (object) []] : $schema;
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

        if ($this->placeholder !== null) {
            $props['placeholder'] = $this->placeholder;
        }

        if (! $this->iconsOnly) {
            $props['iconsOnly'] = false;
        }

        if ($this->allowCustom) {
            $props['allowCustom'] = true;
        }

        return $props;
    }

    /** Reordering and “All” are only meaningful on a multi-select checkbox list. */
    private function asCheckboxes(): void
    {
        $this->multiple = true;

        if ($this->presentation !== ChoicePresentation::AddMenu) {
            $this->presentation = ChoicePresentation::Checkboxes;
        }
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
    private static function selectOptions(array $options, bool $required, string|false|null $placeholder): array
    {
        if ($required || $placeholder === false) {
            return $options;
        }

        foreach ($options as $option) {
            if (($option['value'] ?? null) === '') {
                return $options;
            }
        }

        return [['label' => $placeholder ?? '', 'value' => ''], ...$options];
    }

    /** @param array<string, mixed> $attributes */
    private static function selectHtml(ControlPayload $control, mixed $value, array $attributes): string
    {
        return self::select($control, $attributes)
            ->options(self::selectOptions(
                $control->props['options'],
                (bool) $attributes['required'],
                $control->props['placeholder'] ?? null,
            ))
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
        if (($control->props['sortable'] ?? false) || isset($control->props['allLabel'])) {
            return self::checkboxSelectHtml($control, $value, $attributes);
        }

        $values = self::values($value);
        $name = self::multipleName($attributes['name']);
        $checkboxes = array_map(fn (array $option, int $index): Checkbox => self::checkbox(
            $option,
            $index,
            $attributes,
            $name,
            in_array((string) $option['value'], $values, true),
        ), $control->props['options'], array_keys($control->props['options']));

        return CheckboxGroup::make()
            ->id($attributes['id'])
            ->name($attributes['name'])
            ->options($checkboxes)
            ->customOptionTemplate(($control->props['allowCustom'] ?? false) && $name !== null
                ? FormFields::customCheckboxTemplate($name, $attributes['aria']['describedby'] ?? null)
                : null)
            ->attributes(self::groupAttributes($attributes, 'group'))
            ->toHtml();
    }

    /**
     * Renders through {@see CheckboxSelect}, for sortable options, an “All”
     * option, or the “Add item” menu.
     *
     * In single-value mode a checked “All” speaks for the options: they render
     * checked, so the list reads as fully selected, but without their names,
     * so only its value posts. The element restores the names once it's
     * unchecked.
     *
     * @param  array<string, mixed>  $attributes
     */
    private static function checkboxSelectHtml(ControlPayload $control, mixed $value, array $attributes): string
    {
        $name = $attributes['name'];
        $values = self::values($value);
        $allLabel = $control->props['allLabel'] ?? null;
        $allValue = (string) ($control->props['allValue'] ?? self::ALL_VALUE);
        $allMode = AllOptionMode::tryFrom((string) ($control->props['allMode'] ?? '')) ?? AllOptionMode::SingleValue;
        $allChecked = $allLabel !== null
            && $allMode === AllOptionMode::SingleValue
            && in_array($allValue, $values, true);
        $itemName = self::multipleName($name);

        /** @var list<array{label: string, labelHtml?: string, icon?: string, value: bool|float|int|string, disabled?: bool}> $options */
        $options = $control->props['options'];

        // The value carries the display order, so selected options lead.
        if (($control->props['sortable'] ?? false) && $values !== []) {
            usort($options, fn (array $a, array $b): int => self::valueOrder($a, $values) <=> self::valueOrder($b, $values));
        }

        $checkboxes = array_map(function (array $option, int $index) use ($attributes, $itemName, $values, $allChecked): Checkbox {
            $checkbox = self::checkbox(
                $option,
                $index,
                $attributes,
                $allChecked ? null : $itemName,
                $allChecked || in_array((string) $option['value'], $values, true),
            );

            return $allChecked && $itemName !== null
                ? $checkbox->inputAttributes([
                    'data' => ['governed-name' => $itemName],
                    'aria' => ['invalid' => $attributes['aria']['invalid'] ?? null],
                ])
                : $checkbox;
        }, $options, array_keys($options));

        $select = CheckboxSelect::make()
            ->id($attributes['id'])
            ->name($name)
            ->options($checkboxes)
            ->sortable((bool) ($control->props['sortable'] ?? false))
            ->presentation($control->props['presentation'] === ChoicePresentation::AddMenu->value
                ? CheckboxSelectPresentation::AddMenu
                : CheckboxSelectPresentation::Checkboxes)
            ->disabled($name === null)
            ->attributes(self::groupAttributes($attributes, 'group'));

        if ($allLabel !== null) {
            $select->allOption($allLabel, $allValue, $allMode, $allChecked);
        }

        return $select->toHtml();
    }

    /**
     * @param  array{label: string, labelHtml?: string, icon?: string, color?: string, custom?: bool, value: bool|float|int|string, disabled?: bool}  $option
     * @param  array<string, mixed>  $attributes
     */
    private static function checkbox(array $option, int $index, array $attributes, ?string $name, bool $checked): Checkbox
    {
        return Checkbox::make()
            ->id("{$attributes['id']}-{$index}")
            ->name($name)
            ->value((string) $option['value'])
            ->checked($checked)
            ->disabled($attributes['name'] === null || ($option['disabled'] ?? false))
            ->label(self::optionLabel($option))
            ->icon($option['icon'] ?? null)
            ->color($option['color'] ?? null)
            ->custom(($option['custom'] ?? false) ? FormFields::textHtml([
                'value' => (string) $option['value'],
                'class' => 'small custom-option-input',
                'labelledBy' => "{$attributes['id']}-{$index}-label",
            ]) : null)
            ->describedBy($attributes['aria']['describedby'] ?? null)
            ->inputAttributes([
                'aria' => ['invalid' => $attributes['aria']['invalid'] ?? null],
            ]);
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
     * With `$buttons`, the radios are drawn as a row of joined buttons, and an
     * option with an icon shows only the icon, named by the option's label,
     * unless the control isn't {@see self::iconsOnly()}.
     *
     * @param  array<string, mixed>  $attributes
     */
    private static function radiosHtml(ControlPayload $control, mixed $value, array $attributes, bool $buttons = false): string
    {
        $values = self::values($value);
        $iconsOnly = $control->props['iconsOnly'] ?? true;
        $allowCustom = ! $buttons && ($control->props['allowCustom'] ?? false);
        $options = $control->props['options'];
        $customOption = null;

        if ($allowCustom) {
            $customOption = Arr::first($options, fn (array $option): bool => $option['custom'] ?? false);
            $options = array_values(array_filter($options, fn (array $option): bool => ! ($option['custom'] ?? false)));
        }

        $radios = array_map(function (array $option, int $index) use ($attributes, $values, $buttons, $iconsOnly): Radio {
            $optionValue = (string) $option['value'];
            $icon = $buttons && $iconsOnly ? ($option['icon'] ?? null) : null;

            return Radio::make()
                ->id("{$attributes['id']}-{$index}")
                ->name($attributes['name'])
                ->value($optionValue)
                ->checked(in_array($optionValue, $values, true))
                ->disabled($attributes['name'] === null || ($option['disabled'] ?? false))
                ->label($icon !== null ? Icon::make()->name($icon)->label($option['label']) : self::optionLabel($option))
                ->attributes(['title' => $icon !== null ? $option['label'] : null])
                ->icon($icon === null ? ($option['icon'] ?? null) : null)
                ->color($buttons ? null : ($option['color'] ?? null))
                ->thumbnail($option['thumbnail'] ?? null)
                ->describedBy($attributes['aria']['describedby'] ?? null)
                ->inputAttributes([
                    'required' => $attributes['required'],
                    'aria' => ['invalid' => $attributes['aria']['invalid'] ?? null],
                ]);
        }, $options, array_keys($options));

        if ($allowCustom) {
            $radios[] = FormFields::radioFromConfig([
                'id' => "{$attributes['id']}-custom",
                'name' => $attributes['name'],
                'value' => $customOption !== null ? (string) $customOption['value'] : '',
                'checked' => $customOption !== null && in_array((string) $customOption['value'], $values, true),
                'disabled' => $attributes['name'] === null,
                'describedBy' => $attributes['aria']['describedby'] ?? null,
                'custom' => true,
            ]);
        }

        return RadioGroup::make()
            ->id($attributes['id'])
            ->buttons($buttons)
            ->options($radios)
            ->attributes(self::groupAttributes($attributes, 'radiogroup'))
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
        $iconsOnly = $control->props['iconsOnly'] ?? true;

        return array_map(function (array $option) use ($attributes, $values, $iconsOnly): Button {
            $optionValue = (string) $option['value'];

            $icon = $option['icon'] ?? null;
            $iconOnly = $iconsOnly && $icon !== null;

            return Button::make()
                ->icon($icon)
                // An icon-only button keeps its light DOM empty, which is what
                // `<craft-button>` keys its square treatment on. The option's
                // label becomes the button's accessible name instead.
                ->label($iconOnly ? null : self::optionLabel($option))
                ->accessibleName($iconOnly ? $option['label'] : null)
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
