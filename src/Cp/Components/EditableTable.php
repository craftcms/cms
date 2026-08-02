<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use Closure;
use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\Cp\Forms\Contracts\FormElement;
use CraftCms\Cms\Cp\Forms\Data\FormElementData;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Json;
use Override;

/**
 * @phpstan-type EditableTableOption array{
 *     label: string,
 *     value: string|int|float|bool|null,
 *     default?: bool,
 * }
 * @phpstan-type EditableTableColumn array{
 *     key: string,
 *     label: string,
 *     type: 'checkbox'|'color'|'date'|'email'|'icon'|'lightswitch'|'multiline'|'number'|'select'|'text'|'time'|'url',
 *     width?: string|int,
 *     class?: string,
 *     code?: bool,
 *     placeholder?: string,
 *     autoPopulate?: string,
 *     nestedOptions?: bool,
 *     radioMode?: bool,
 *     toggle?: list<string>,
 *     options?: list<EditableTableOption>,
 * }
 * @phpstan-type EditableTableFixedRow array{key: string, label: string}
 */
class EditableTable extends ViewComponent implements FormElement
{
    private const array COLUMN_TYPES = [
        'checkbox',
        'color',
        'date',
        'email',
        'icon',
        'lightswitch',
        'multiline',
        'number',
        'select',
        'text',
        'time',
        'url',
    ];

    protected string|Closure|null $name = null;

    protected string|Closure|null $sourceName = null;

    /** @var array<array-key, mixed>|Closure */
    protected array|Closure $value = [];

    /** @var list<EditableTableColumn>|Closure */
    protected array|Closure $columns = [];

    /** @var list<EditableTableFixedRow>|Closure */
    protected array|Closure $fixedRows = [];

    protected string|Closure|null $addRowLabel = null;

    /** @var array<string, mixed>|Closure */
    protected array|Closure $defaultRow = [];

    protected bool|Closure $keyed = false;

    protected bool|Closure $includeRowId = false;

    protected bool|Closure $definesColumns = false;

    protected string|Closure|null $columnsFrom = null;

    protected bool|Closure $readOnly = false;

    /** @var array<string, mixed> */
    protected array $formElementAttributes = [];

    public static function formElementType(): string
    {
        return 'craft:editable-table-input';
    }

    public static function isFormElementContainer(): bool
    {
        return false;
    }

    #[Override]
    protected function tagName(): string
    {
        return 'craft-editable-table';
    }

    public function name(string|Closure|null $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function sourceName(string|Closure|null $sourceName): static
    {
        $this->sourceName = $sourceName;

        return $this;
    }

    /** @param array<array-key, mixed>|Closure $value */
    public function value(array|Closure $value): static
    {
        $this->value = $value;

        return $this;
    }

    /** @param list<EditableTableColumn>|Closure $columns */
    public function columns(array|Closure $columns): static
    {
        $this->columns = $columns;

        return $this;
    }

    /** @param list<EditableTableFixedRow>|Closure $fixedRows */
    public function fixedRows(array|Closure $fixedRows): static
    {
        $this->fixedRows = $fixedRows;

        return $this;
    }

    public function addRowLabel(string|Closure|null $addRowLabel): static
    {
        $this->addRowLabel = $addRowLabel;

        return $this;
    }

    /** @param array<string, mixed>|Closure $defaultRow */
    public function defaultRow(array|Closure $defaultRow): static
    {
        $this->defaultRow = $defaultRow;

        return $this;
    }

    public function keyed(bool|Closure $keyed = true): static
    {
        $this->keyed = $keyed;

        return $this;
    }

    public function includeRowId(bool|Closure $includeRowId = true): static
    {
        $this->includeRowId = $includeRowId;

        return $this;
    }

    public function definesColumns(bool|Closure $definesColumns = true): static
    {
        $this->definesColumns = $definesColumns;

        return $this;
    }

    public function columnsFrom(string|Closure|null $columnsFrom): static
    {
        $this->columnsFrom = $columnsFrom;

        return $this;
    }

    public function readOnly(bool|Closure $readOnly = true): static
    {
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
        $name = $this->portableText('name', $this->name);

        if ($name === null) {
            $this->unsupportedOutputOption('name', 'Form');
        }

        $columns = $this->resolvedColumns('Form');
        $fixedRows = $this->resolvedFixedRows('Form');
        $defaultRow = $this->resolvedDefaultRow('Form');
        $keyed = $this->resolvedBool('keyed', $this->keyed, 'Form');
        $includeRowId = $this->resolvedBool('includeRowId', $this->includeRowId, 'Form');
        $definesColumns = $this->resolvedBool('definesColumns', $this->definesColumns, 'Form');
        $readOnly = $this->resolvedBool('readOnly', $this->readOnly, 'Form');
        $addRowLabel = $this->portableText('addRowLabel', $this->addRowLabel);
        $value = $this->evaluate($this->value);

        $this->validateValue($value, $keyed, 'Form');

        $props = array_filter([
            'tableHtml' => $this->tableHtml(
                $name,
                $value,
                $columns,
                $fixedRows,
                $defaultRow,
                $keyed,
                $includeRowId,
                $readOnly,
                $addRowLabel,
            ),
            'sourceName' => $this->portableText('sourceName', $this->sourceName),
            'columns' => $columns,
            'fixedRows' => $fixedRows === [] ? null : $fixedRows,
            'addRowLabel' => $addRowLabel,
            'defaultRow' => $defaultRow === [] ? null : $defaultRow,
            'keyed' => $keyed ?: null,
            'includeRowId' => $includeRowId ?: null,
            'definesColumns' => $definesColumns ?: null,
            'columnsFrom' => $this->portableText('columnsFrom', $this->columnsFrom),
        ], fn (mixed $value): bool => $value !== null);
        $attributes = $this->formElementAttributes();

        return new FormElementData(
            type: static::formElementType(),
            name: $name,
            props: $props,
            attributes: $attributes === [] ? null : $attributes,
        );
    }

    #[Override]
    protected function renderMarkup(): string
    {
        $columns = $this->resolvedColumns('HTML');
        $fixedRows = $this->resolvedFixedRows('HTML');
        $defaultRow = $this->resolvedDefaultRow('HTML');
        $keyed = $this->resolvedBool('keyed', $this->keyed, 'HTML');
        $value = $this->evaluate($this->value);

        $this->validateValue($value, $keyed, 'HTML');

        return $this->tableHtml(
            $this->resolvedText('name', $this->name, 'HTML') ?? 'editableTable',
            $value,
            $columns,
            $fixedRows,
            $defaultRow,
            $keyed,
            $this->resolvedBool('includeRowId', $this->includeRowId, 'HTML'),
            $this->resolvedBool('readOnly', $this->readOnly, 'HTML'),
            $this->resolvedText('addRowLabel', $this->addRowLabel, 'HTML'),
            $this->attributes,
        );
    }

    /**
     * @param  array<array-key, mixed>  $value
     * @param  list<EditableTableColumn>  $columns
     * @param  list<EditableTableFixedRow>  $fixedRows
     * @param  array<string, mixed>  $defaultRow
     */
    private function tableHtml(
        string $name,
        array $value,
        array $columns,
        array $fixedRows,
        array $defaultRow,
        bool $keyed,
        bool $includeRowId,
        bool $readOnly,
        ?string $addRowLabel,
        array $attributes = [],
    ): string {
        $legacyColumns = $this->legacyColumns($columns);
        $rows = $value;

        if ($fixedRows !== []) {
            $legacyColumns = [
                '__rowLabel' => ['type' => 'heading', 'heading' => ''],
                ...$legacyColumns,
            ];
            $rows = [];

            foreach ($fixedRows as $fixedRow) {
                $rows[$fixedRow['key']] = [
                    '__rowLabel' => $fixedRow['label'],
                    ...($value[$fixedRow['key']] ?? []),
                ];
            }
        }

        $mutable = ! $readOnly && $fixedRows === [];
        $id = Html::id("editable-table-{$name}-".spl_object_id($this));
        $table = FormFields::editableTableHtml([
            'id' => $id,
            'name' => $name,
            'cols' => $legacyColumns,
            'rows' => $rows,
            'defaultValues' => $defaultRow,
            'addRowLabel' => $addRowLabel,
            'allowAdd' => $mutable,
            'allowReorder' => $mutable,
            'allowDelete' => $mutable,
            'static' => $readOnly,
            'includeRowId' => $includeRowId,
            'initJs' => false,
        ]);

        return Html::tag('craft-editable-table', $table, [
            ...$attributes,
            'name' => InputNamespace::namespaceInputName($name),
            'cols' => Json::encode($legacyColumns, JSON_THROW_ON_ERROR),
            'settings' => Json::encode([
                'rowIdPrefix' => $keyed ? 'new' : '',
                'defaultValues' => $defaultRow,
                'allowAdd' => $mutable,
                'allowReorder' => $mutable,
                'allowDelete' => $mutable,
                'staticRows' => $readOnly,
                'includeRowId' => $includeRowId,
                'values' => $value,
            ], JSON_THROW_ON_ERROR),
            'keyed' => $keyed,
        ]);
    }

    /**
     * @param  list<EditableTableColumn>  $columns
     * @return array<string, array<string, mixed>>
     */
    private function legacyColumns(array $columns): array
    {
        return array_column(array_map(static fn (array $column): array => [
            'key' => $column['key'],
            'column' => [
                ...$column,
                'heading' => $column['label'],
                'type' => $column['type'] === 'text' ? 'singleline' : $column['type'],
                ...(isset($column['autoPopulate']) ? ['autopopulate' => $column['autoPopulate']] : []),
            ],
        ], $columns), 'column', 'key');
    }

    /** @return list<EditableTableColumn> */
    private function resolvedColumns(string $output): array
    {
        $columns = $this->evaluate($this->columns);

        if (! is_array($columns) || ! array_is_list($columns)) {
            $this->unsupportedOutputOption('columns', $output);
        }

        foreach ($columns as $index => $column) {
            $this->validateColumn($column, $index, $output);
        }

        return $columns;
    }

    /** @return list<EditableTableFixedRow> */
    private function resolvedFixedRows(string $output): array
    {
        $fixedRows = $this->evaluate($this->fixedRows);

        if (! is_array($fixedRows) || ! array_is_list($fixedRows)) {
            $this->unsupportedOutputOption('fixedRows', $output);
        }

        foreach ($fixedRows as $index => $row) {
            if (
                ! is_array($row)
                || array_diff(array_keys($row), ['key', 'label']) !== []
                || ! is_string($row['key'] ?? null)
                || ! is_string($row['label'] ?? null)
            ) {
                $this->unsupportedOutputOption("fixedRows[{$index}]", $output);
            }
        }

        return $fixedRows;
    }

    /** @return array<string, mixed> */
    private function resolvedDefaultRow(string $output): array
    {
        $defaultRow = $this->evaluate($this->defaultRow);

        if (! is_array($defaultRow) || ($defaultRow !== [] && array_is_list($defaultRow))) {
            $this->unsupportedOutputOption('defaultRow', $output);
        }

        $this->validateJsonValue($defaultRow, 'defaultRow', $output);

        return $defaultRow;
    }

    private function validateColumn(mixed $column, int $index, string $output): void
    {
        if (! is_array($column)) {
            $this->unsupportedOutputOption("columns[{$index}]", $output);
        }

        $supported = ['key', 'label', 'type', 'width', 'class', 'code', 'placeholder', 'autoPopulate', 'nestedOptions', 'radioMode', 'toggle', 'options'];

        foreach (array_keys($column) as $property) {
            if (! in_array($property, $supported, true)) {
                $this->unsupportedOutputOption("columns[{$index}].{$property}", $output);
            }
        }

        foreach (['key', 'label', 'type'] as $property) {
            if (! isset($column[$property]) || ! is_string($column[$property])) {
                $this->unsupportedOutputOption("columns[{$index}].{$property}", $output);
            }
        }

        if (! in_array($column['type'], self::COLUMN_TYPES, true)) {
            $this->unsupportedOutputOption("columns[{$index}].type", $output);
        }

        if (array_key_exists('width', $column) && ! is_string($column['width']) && ! is_int($column['width'])) {
            $this->unsupportedOutputOption("columns[{$index}].width", $output);
        }

        foreach (['class', 'placeholder', 'autoPopulate'] as $property) {
            if (array_key_exists($property, $column) && ! is_string($column[$property])) {
                $this->unsupportedOutputOption("columns[{$index}].{$property}", $output);
            }
        }

        foreach (['code', 'nestedOptions', 'radioMode'] as $property) {
            if (array_key_exists($property, $column) && ! is_bool($column[$property])) {
                $this->unsupportedOutputOption("columns[{$index}].{$property}", $output);
            }
        }

        if (array_key_exists('toggle', $column) && (! is_array($column['toggle']) || ! array_is_list($column['toggle']) || ! array_all($column['toggle'], fn (mixed $target): bool => is_string($target)))) {
            $this->unsupportedOutputOption("columns[{$index}].toggle", $output);
        }

        if (array_key_exists('options', $column)) {
            $this->validateOptions($column['options'], $index, $output);
        }
    }

    private function validateOptions(mixed $options, int $columnIndex, string $output): void
    {
        if (! is_array($options) || ! array_is_list($options)) {
            $this->unsupportedOutputOption("columns[{$columnIndex}].options", $output);
        }

        foreach ($options as $index => $option) {
            if (! is_array($option)) {
                $this->unsupportedOutputOption("columns[{$columnIndex}].options[{$index}]", $output);
            }

            foreach (array_keys($option) as $property) {
                if (! in_array($property, ['label', 'value', 'default'], true)) {
                    $this->unsupportedOutputOption("columns[{$columnIndex}].options[{$index}].{$property}", $output);
                }
            }

            if (! isset($option['label']) || ! is_string($option['label']) || ! array_key_exists('value', $option)) {
                $this->unsupportedOutputOption("columns[{$columnIndex}].options[{$index}]", $output);
            }

            if (array_key_exists('default', $option) && ! is_bool($option['default'])) {
                $this->unsupportedOutputOption("columns[{$columnIndex}].options[{$index}].default", $output);
            }

            $value = $option['value'];

            if (! is_scalar($value) && $value !== null) {
                $this->unsupportedOutputOption("columns[{$columnIndex}].options[{$index}].value", $output);
            }

            if (is_float($value) && ! is_finite($value)) {
                $this->unsupportedOutputOption("columns[{$columnIndex}].options[{$index}].value", $output);
            }
        }
    }

    private function validateValue(mixed $value, bool $keyed, string $output): void
    {
        if (
            ! is_array($value)
            || (! $keyed && ! array_is_list($value))
            || ($keyed && $value !== [] && array_is_list($value))
        ) {
            $this->unsupportedOutputOption('value', $output);
        }

        foreach ($value as $key => $row) {
            if (! is_array($row)) {
                $this->unsupportedOutputOption("value[{$key}]", $output);
            }

            $this->validateJsonValue($row, "value[{$key}]", $output);
        }
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
            $this->validateJsonValue($item, "{$option}[{$key}]", $output);
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

    /** @return array<string, mixed> */
    private function formElementAttributes(): array
    {
        return $this->withoutAttributes($this->formElementAttributes, [
            'add-row-label',
            'aria-describedby',
            'aria-labelledby',
            'columns',
            'columns-from',
            'default-row',
            'defines-columns',
            'fixed-rows',
            'id',
            'include-row-id',
            'keyed',
            'name',
            'readonly',
            'slot',
            'source-name',
            'value',
        ]);
    }
}
