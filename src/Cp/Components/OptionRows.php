<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use Closure;
use CraftCms\Cms\Cp\FormDefinitions\Contracts\FormElement;
use CraftCms\Cms\Cp\FormDefinitions\Data\FormElementData;
use CraftCms\Cms\Support\Json;
use Override;

class OptionRows extends ViewComponent implements FormElement
{
    protected string|Closure|null $name = null;

    /** @var array<array-key, mixed>|Closure */
    protected array|Closure $value = [];

    protected bool|Closure $multipleDefaults = false;

    protected bool|Closure $optgroups = false;

    protected bool|Closure $icons = false;

    protected bool|Closure $colors = false;

    protected bool|Closure $readOnly = false;

    /** @var array<string, mixed> */
    protected array $formElementAttributes = [];

    public static function formElementType(): string
    {
        return 'craft:option-rows';
    }

    public static function isFormElementContainer(): bool
    {
        return false;
    }

    #[Override]
    protected function tagName(): string
    {
        return 'craft-option-rows';
    }

    public function name(string|Closure|null $name): static
    {
        $this->trackConfiguration('name');
        $this->name = $name;

        return $this;
    }

    /** @param array<array-key, mixed>|Closure $value */
    public function value(array|Closure $value): static
    {
        $this->trackConfiguration('value');
        $this->value = $value;

        return $this;
    }

    public function multipleDefaults(bool|Closure $multipleDefaults = true): static
    {
        $this->trackConfiguration('multipleDefaults');
        $this->multipleDefaults = $multipleDefaults;

        return $this;
    }

    public function optgroups(bool|Closure $optgroups = true): static
    {
        $this->trackConfiguration('optgroups');
        $this->optgroups = $optgroups;

        return $this;
    }

    public function icons(bool|Closure $icons = true): static
    {
        $this->trackConfiguration('icons');
        $this->icons = $icons;

        return $this;
    }

    public function colors(bool|Closure $colors = true): static
    {
        $this->trackConfiguration('colors');
        $this->colors = $colors;

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
        $this->rejectConfiguredOptions(['value', 'readOnly', 'slot'], 'Form Definition');

        $name = $this->portableText('name', $this->name);

        if ($name === null) {
            $this->unsupportedOutputOption('name', 'Form Definition');
        }

        $props = array_filter([
            'multipleDefaults' => $this->resolvedBool('multipleDefaults', $this->multipleDefaults, 'Form Definition'),
            'optgroups' => $this->resolvedBool('optgroups', $this->optgroups, 'Form Definition'),
            'icons' => $this->resolvedBool('icons', $this->icons, 'Form Definition'),
            'colors' => $this->resolvedBool('colors', $this->colors, 'Form Definition'),
        ]);

        foreach (array_keys($this->formElementAttributes) as $attribute) {
            if (in_array(strtolower((string) $attribute), [
                'aria-describedby',
                'aria-labelledby',
                'colors',
                'icons',
                'id',
                'multiple-defaults',
                'name',
                'optgroups',
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
            props: $props === [] ? null : $props,
            attributes: $this->formElementAttributes === [] ? null : $this->formElementAttributes,
        );
    }

    #[Override]
    protected function hostAttributes(): array
    {
        $rows = $this->evaluate($this->value);
        $multipleDefaults = $this->resolvedBool('multipleDefaults', $this->multipleDefaults, 'HTML');
        $optgroups = $this->resolvedBool('optgroups', $this->optgroups, 'HTML');
        $icons = $this->resolvedBool('icons', $this->icons, 'HTML');
        $colors = $this->resolvedBool('colors', $this->colors, 'HTML');

        $this->validateRows($rows, $optgroups, $icons, $colors);

        return [
            'name' => $this->evaluate($this->name),
            'value' => Json::encode($rows, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'multiple-defaults' => $multipleDefaults,
            'optgroups' => $optgroups,
            'icons' => $icons,
            'colors' => $colors,
            'readonly' => $this->resolvedBool('readOnly', $this->readOnly, 'HTML'),
        ];
    }

    private function resolvedBool(string $option, bool|Closure $value, string $output): bool
    {
        $value = $this->evaluate($value);

        if (! is_bool($value)) {
            $this->unsupportedOutputOption($option, $output);
        }

        return $value;
    }

    private function validateRows(mixed $rows, bool $optgroups, bool $icons, bool $colors): void
    {
        if (! is_array($rows) || ! array_is_list($rows)) {
            $this->unsupportedOutputOption('value', 'HTML');
        }

        foreach ($rows as $index => $row) {
            if (! is_array($row)) {
                $this->unsupportedOutputOption("value[{$index}]", 'HTML');
            }

            if (array_key_exists('optgroup', $row)) {
                $this->validateRowProperties($row, $index, ['optgroup', 'disabled']);

                if (! $optgroups) {
                    $this->unsupportedOutputOption("value[{$index}].optgroup", 'HTML');
                }

                if (! $this->isTextValue($row['optgroup'])) {
                    $this->unsupportedOutputOption("value[{$index}].optgroup", 'HTML');
                }

                if (array_key_exists('disabled', $row) && ! $this->isBooleanValue($row['disabled'])) {
                    $this->unsupportedOutputOption("value[{$index}].disabled", 'HTML');
                }

                continue;
            }

            $this->validateRowProperties($row, $index, ['label', 'value', 'icon', 'color', 'default', 'disabled']);

            if (
                ! array_key_exists('label', $row)
                || ! array_key_exists('value', $row)
                || ! $this->isTextValue($row['label'])
                || ! $this->isTextValue($row['value'])
            ) {
                $this->unsupportedOutputOption("value[{$index}]", 'HTML');
            }

            if (isset($row['icon']) && $row['icon'] !== '' && ! $icons) {
                $this->unsupportedOutputOption("value[{$index}].icon", 'HTML');
            }

            if (isset($row['color']) && $row['color'] !== '' && ! $colors) {
                $this->unsupportedOutputOption("value[{$index}].color", 'HTML');
            }

            foreach (['icon', 'color'] as $property) {
                if (array_key_exists($property, $row) && ! $this->isTextValue($row[$property])) {
                    $this->unsupportedOutputOption("value[{$index}].{$property}", 'HTML');
                }
            }

            foreach (['default', 'disabled'] as $property) {
                if (array_key_exists($property, $row) && ! $this->isBooleanValue($row[$property])) {
                    $this->unsupportedOutputOption("value[{$index}].{$property}", 'HTML');
                }
            }
        }
    }

    /** @param array<array-key, mixed> $row @param list<string> $supported */
    private function validateRowProperties(array $row, int $index, array $supported): void
    {
        foreach (array_keys($row) as $property) {
            if (! in_array($property, $supported, true)) {
                $this->unsupportedOutputOption("value[{$index}].{$property}", 'HTML');
            }
        }
    }

    private function isTextValue(mixed $value): bool
    {
        if ($value === null || is_string($value) || is_int($value) || is_float($value)) {
            return true;
        }

        return is_array($value)
            && array_key_exists('value', $value)
            && array_diff(array_keys($value), ['value', 'hasErrors']) === []
            && (! array_key_exists('hasErrors', $value) || is_bool($value['hasErrors']))
            && $this->isTextValue($value['value']);
    }

    private function isBooleanValue(mixed $value): bool
    {
        return is_bool($value)
            || in_array($value, [0, 1, '', '0', '1'], true);
    }
}
