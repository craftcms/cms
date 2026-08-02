<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use Closure;
use CraftCms\Cms\Cp\FormDefinitions\Contracts\FormElement;
use CraftCms\Cms\Cp\FormDefinitions\Data\FormElementData;
use CraftCms\Cms\Support\Json;
use Override;

class KeyedTable extends ViewComponent implements FormElement
{
    protected string|Closure|null $name = null;

    /** @var array<string, array<string, mixed>>|Closure */
    protected array|Closure $value = [];

    /** @var list<array{key: string, label: string, placeholder?: string, code?: bool}>|Closure */
    protected array|Closure $columns = [];

    /** @var list<array{key: string, label: string}>|Closure */
    protected array|Closure $rows = [];

    protected bool|Closure $readOnly = false;

    /** @var array<string, mixed> */
    protected array $formElementAttributes = [];

    public static function formElementType(): string
    {
        return 'craft:keyed-table-input';
    }

    public static function isFormElementContainer(): bool
    {
        return false;
    }

    #[Override]
    protected function tagName(): string
    {
        return 'craft-keyed-table';
    }

    public function name(string|Closure|null $name): static
    {
        $this->trackConfiguration('name');
        $this->name = $name;

        return $this;
    }

    /** @param array<string, array<string, mixed>>|Closure $value */
    public function value(array|Closure $value): static
    {
        $this->trackConfiguration('value');
        $this->value = $value;

        return $this;
    }

    /** @param list<array{key: string, label: string, placeholder?: string, code?: bool}>|Closure $columns */
    public function columns(array|Closure $columns): static
    {
        $this->trackConfiguration('columns');
        $this->columns = $columns;

        return $this;
    }

    /** @param list<array{key: string, label: string}>|Closure $rows */
    public function rows(array|Closure $rows): static
    {
        $this->trackConfiguration('rows');
        $this->rows = $rows;

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

        $columns = $this->resolvedColumns('Form Definition');
        $rows = $this->resolvedRows('Form Definition');

        $this->validateAttributes();

        return new FormElementData(
            type: static::formElementType(),
            name: $name,
            props: ['columns' => $columns, 'rows' => $rows],
            attributes: $this->formElementAttributes === [] ? null : $this->formElementAttributes,
        );
    }

    #[Override]
    protected function hostAttributes(): array
    {
        $columns = $this->resolvedColumns('HTML');
        $rows = $this->resolvedRows('HTML');
        $value = $this->evaluate($this->value);

        $this->validateValue($value, 'HTML');

        return [
            'name' => $this->resolvedText('name', $this->name, 'HTML'),
            'value' => $value === [] ? '{}' : Json::encode($value, JSON_THROW_ON_ERROR),
            'columns' => Json::encode($columns, JSON_THROW_ON_ERROR),
            'rows' => Json::encode($rows, JSON_THROW_ON_ERROR),
            'readonly' => $this->resolvedBool('readOnly', $this->readOnly, 'HTML'),
        ];
    }

    /** @return list<array{key: string, label: string, placeholder?: string, code?: bool}> */
    private function resolvedColumns(string $output): array
    {
        $columns = $this->evaluate($this->columns);

        if (! is_array($columns) || ! array_is_list($columns)) {
            $this->unsupportedOutputOption('columns', $output);
        }

        foreach ($columns as $index => $column) {
            $this->validateDefinition($column, $index, ['key', 'label', 'placeholder', 'code'], $output, 'columns');

            if (array_key_exists('placeholder', $column) && ! is_string($column['placeholder'])) {
                $this->unsupportedOutputOption("columns[{$index}].placeholder", $output);
            }

            if (array_key_exists('code', $column) && ! is_bool($column['code'])) {
                $this->unsupportedOutputOption("columns[{$index}].code", $output);
            }
        }

        return $columns;
    }

    /** @return list<array{key: string, label: string}> */
    private function resolvedRows(string $output): array
    {
        $rows = $this->evaluate($this->rows);

        if (! is_array($rows) || ! array_is_list($rows)) {
            $this->unsupportedOutputOption('rows', $output);
        }

        foreach ($rows as $index => $row) {
            $this->validateDefinition($row, $index, ['key', 'label'], $output, 'rows');
        }

        return $rows;
    }

    /** @param list<string> $supported */
    private function validateDefinition(mixed $definition, int $index, array $supported, string $output, string $option): void
    {
        if (! is_array($definition)) {
            $this->unsupportedOutputOption("{$option}[{$index}]", $output);
        }

        foreach (array_keys($definition) as $property) {
            if (! in_array($property, $supported, true)) {
                $this->unsupportedOutputOption("{$option}[{$index}].{$property}", $output);
            }
        }

        foreach (['key', 'label'] as $property) {
            if (! isset($definition[$property]) || ! is_string($definition[$property])) {
                $this->unsupportedOutputOption("{$option}[{$index}].{$property}", $output);
            }
        }
    }

    private function validateValue(mixed $value, string $output): void
    {
        if (! is_array($value) || ($value !== [] && array_is_list($value))) {
            $this->unsupportedOutputOption('value', $output);
        }

        foreach ($value as $rowKey => $row) {
            if (! is_array($row)) {
                $this->unsupportedOutputOption("value[{$rowKey}]", $output);
            }

            foreach ($row as $columnKey => $cell) {
                if (! is_scalar($cell) && $cell !== null) {
                    $this->unsupportedOutputOption("value[{$rowKey}][{$columnKey}]", $output);
                }

                if (is_float($cell) && ! is_finite($cell)) {
                    $this->unsupportedOutputOption("value[{$rowKey}][{$columnKey}]", $output);
                }
            }
        }
    }

    private function resolvedBool(string $option, bool|Closure $value, string $output): bool
    {
        $value = $this->evaluate($value);

        if (! is_bool($value)) {
            $this->unsupportedOutputOption($option, $output);
        }

        return $value;
    }

    private function resolvedText(string $option, string|Closure|null $value, string $output): ?string
    {
        $value = $this->evaluate($value);

        if ($value !== null && ! is_string($value)) {
            $this->unsupportedOutputOption($option, $output);
        }

        return $value;
    }

    private function validateAttributes(): void
    {
        foreach (array_keys($this->formElementAttributes) as $attribute) {
            if (in_array(strtolower((string) $attribute), [
                'aria-describedby',
                'aria-labelledby',
                'columns',
                'id',
                'name',
                'readonly',
                'rows',
                'slot',
                'value',
            ], true)) {
                $this->unsupportedOutputOption("attributes.{$attribute}", 'Form Definition');
            }
        }
    }
}
