<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use Closure;
use CraftCms\Cms\Cp\Concerns\HasDisabled;
use CraftCms\Cms\Cp\FormDefinitions\Contracts\FormElement;
use CraftCms\Cms\Cp\FormDefinitions\Data\FormElementData;
use CraftCms\Cms\Support\Html;

/**
 * Checkbox select — the PHP counterpart to the legacy
 * `_includes/forms/checkboxSelect` template. Renders a
 * `craft-checkbox-select.cp-checkbox-select` of {@see Checkbox} items, with an optional
 * "All" checkbox (or an always-post hidden input) first, optionally wrapped
 * in a `<craft-sortable-checkbox-select>` for drag reordering.
 *
 *     CheckboxSelect::make()
 *         ->id('sources')
 *         ->name('sources')
 *         ->allCheckbox(Checkbox::make()->label(t('All'))->name('sources')->value('*'))
 *         ->options([...]);
 */
class CheckboxSelect extends ChoiceGroup implements FormElement
{
    use HasDisabled;

    protected Checkbox|Closure|null $allCheckbox = null;

    protected string|int|float|bool|Closure|null $allOption = null;

    protected bool $hasAllOption = false;

    protected array|string|int|float|bool|Closure|null $values = [];

    protected bool|Closure $sortable = false;

    protected string|Closure|null $storageKey = null;

    protected ?Checkbox $resolvedAllCheckbox = null;

    /** @var array<string, mixed> */
    protected array $formElementAttributes = [];

    public static function formElementType(): string
    {
        return 'craft:checkbox-select-input';
    }

    public static function isFormElementContainer(): bool
    {
        return false;
    }

    protected function tagName(): string
    {
        return 'craft-checkbox-select';
    }

    /** The "All" checkbox, rendered before the items. */
    public function allCheckbox(Checkbox|Closure|null $allCheckbox): static
    {
        $this->trackConfiguration('allCheckbox');
        $this->allCheckbox = $allCheckbox;

        return $this;
    }

    public function allOption(string|int|float|bool|Closure|null $value): static
    {
        $this->trackConfiguration('allOption');
        $this->allOption = $value;
        $this->hasAllOption = true;

        return $this;
    }

    public function values(array|string|int|float|bool|Closure|null $values): static
    {
        $this->trackConfiguration('values');
        $this->values = $values;

        return $this;
    }

    /** Wraps the component in a `<craft-sortable-checkbox-select>`. */
    public function sortable(bool|Closure $sortable = true): static
    {
        $this->trackConfiguration('sortable');
        $this->sortable = $sortable;

        return $this;
    }

    /** Storage key for persisting the sort order client-side. */
    public function storageKey(string|Closure|null $storageKey): static
    {
        $this->trackConfiguration('storageKey');
        $this->storageKey = $storageKey;

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
            'allCheckbox',
            'values',
            'storageKey',
            'disabled',
            'id',
            'slot',
        ], 'Form Definition');

        $name = $this->portableText('name', $this->name);

        if ($name === null) {
            $this->unsupportedOutputOption('name', 'Form Definition');
        }

        $options = $this->evaluate($this->options);

        if (! is_array($options) || ! array_is_list($options) || array_any($options, fn (mixed $option): bool => ! is_array($option))) {
            $this->unsupportedOutputOption('options', 'Form Definition');
        }

        $sortable = $this->evaluate($this->sortable);

        if (! is_bool($sortable)) {
            $this->unsupportedOutputOption('sortable', 'Form Definition');
        }

        $props = ['options' => $options];

        if ($this->hasAllOption) {
            $allOption = $this->evaluate($this->allOption);

            if (! $this->isOptionValue($allOption)) {
                $this->unsupportedOutputOption('allOption', 'Form Definition');
            }

            $props['allOption'] = $allOption;
        }

        if ($sortable) {
            $props['sortable'] = true;
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
            props: $props,
            attributes: $attributes === [] ? null : $attributes,
        );
    }

    #[\Override]
    protected function hostAttributes(): array
    {
        $allOption = $this->hasAllOption ? $this->evaluate($this->allOption) : null;

        return [
            'id' => $this->getId(),
            'name' => $this->evaluate($this->name),
            'all-option' => $this->hasAllOption ? $this->htmlOptionValue($allOption) : null,
            'sortable' => (bool) $this->evaluate($this->sortable),
            'disabled' => $this->isDisabled(),
            'class' => 'cp-checkbox-select',
            'data' => [
                'storage-key' => $this->evaluate($this->storageKey),
            ],
        ];
    }

    /**
     * The "All" checkbox — or, without one, the always-post hidden input for
     * a non-array name.
     */
    #[\Override]
    protected function leadingHtml(): string
    {
        $allCheckbox = $this->resolvedAllCheckbox;

        if ($allCheckbox !== null) {
            return Html::tag('div', $allCheckbox->toHtml());
        }

        $name = $this->evaluate($this->name);

        if ($name !== null && (strlen($name) < 3 || ! str_ends_with($name, '[]'))) {
            return (string) Html::hiddenInput($name, '');
        }

        return '';
    }

    /** @return iterable<ViewComponent> */
    #[\Override]
    protected function evaluatedOptions(): iterable
    {
        $options = $this->evaluate($this->options);
        $this->resolvedAllCheckbox = $this->evaluate($this->allCheckbox);

        if (! is_iterable($options)) {
            $this->unsupportedOutputOption('options', 'HTML');
        }

        $options = is_array($options) ? $options : iterator_to_array($options);

        if (array_all($options, fn (mixed $option): bool => $option instanceof ViewComponent)) {
            return $options;
        }

        if (array_any($options, fn (mixed $option): bool => ! is_array($option))) {
            $this->unsupportedOutputOption('options', 'HTML');
        }

        $values = $this->evaluate($this->values);
        $allOption = $this->hasAllOption ? $this->evaluate($this->allOption) : null;
        $allChecked = $this->hasAllOption && $values == $allOption;
        $name = $this->evaluate($this->name);
        $id = $this->getId();
        $sortable = (bool) $this->evaluate($this->sortable);

        if ($sortable && is_array($values) && $values !== []) {
            usort($options, function (array $a, array $b) use ($values, $allOption): int {
                if (($a['value'] ?? null) === $allOption) {
                    return -1;
                }

                if (($b['value'] ?? null) === $allOption) {
                    return 1;
                }

                $aIndex = array_search($a['value'] ?? null, $values);
                $bIndex = array_search($b['value'] ?? null, $values);

                return ($aIndex === false ? PHP_INT_MAX : $aIndex)
                    <=> ($bIndex === false ? PHP_INT_MAX : $bIndex);
            });
        }

        $checkboxes = [];

        foreach ($options as $index => $option) {
            $optionValue = $option['value'] ?? null;
            $isAll = $this->hasAllOption && $optionValue === $allOption;
            $checkbox = Checkbox::make()
                ->id($id === null ? null : ($isAll ? "{$id}-all" : "{$id}-{$index}"))
                ->name($name === null ? null : ($isAll ? $name : "{$name}[]"))
                ->value($this->htmlOptionValue($optionValue))
                ->label($option['label'] ?? '')
                ->icon($option['icon'] ?? null)
                ->color($option['color'] ?? null)
                ->checked($allChecked || (is_array($values) && in_array($optionValue, $values)))
                ->disabled($allChecked || $this->isDisabled() || (bool) ($option['disabled'] ?? false))
                ->inputAttributes([
                    'data' => ['option-disabled' => ($option['disabled'] ?? false) ? 'true' : 'false'],
                ]);

            if ($isAll) {
                $this->resolvedAllCheckbox = $checkbox;

                continue;
            }

            $checkboxes[] = $checkbox;
        }

        return $checkboxes;
    }

    #[\Override]
    protected function optionWrapperAttributes(ViewComponent $option): array
    {
        return ['class' => 'cp-checkbox-select__item'];
    }

    /** Wraps the component in the sortable web component when enabled. */
    #[\Override]
    protected function renderMarkup(): string
    {
        $html = parent::renderMarkup();

        if (! $this->evaluate($this->sortable)) {
            return $html;
        }

        return Html::tag('craft-sortable-checkbox-select', $html, [
            'disabled' => $this->isDisabled(),
        ]);
    }

    private function isOptionValue(mixed $value): bool
    {
        return $value === null || is_string($value) || is_int($value) || is_float($value) || is_bool($value);
    }

    private function htmlOptionValue(mixed $value): string|int|float
    {
        if ($value === null || is_bool($value)) {
            return (string) $value;
        }

        if (! is_string($value) && ! is_int($value) && ! is_float($value)) {
            $this->unsupportedOutputOption('options', 'HTML');
        }

        return $value;
    }
}
