<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\FormDefinitions\Elements;

use InvalidArgumentException;

/**
 * @phpstan-type EditableTableOption array{
 *     label: string,
 *     value: string|int|float|bool|null,
 *     default?: bool,
 * }
 * @phpstan-type EditableTableColumn array{
 *     key: string,
 *     label: string,
 *     type: 'checkbox'|'color'|'date'|'email'|'lightswitch'|'multiline'|'number'|'select'|'text'|'time'|'url',
 *     width?: string|int,
 *     class?: string,
 *     code?: bool,
 *     autoPopulate?: string,
 *     nestedOptions?: bool,
 *     options?: list<EditableTableOption>,
 * }
 */
class EditableTableInput extends InputElement
{
    private const array COLUMN_TYPES = [
        'checkbox',
        'color',
        'date',
        'email',
        'lightswitch',
        'multiline',
        'number',
        'select',
        'text',
        'time',
        'url',
    ];

    /** @var list<EditableTableColumn> */
    private array $columns = [];

    private ?string $addRowLabel = null;

    /** @var array<string, mixed> */
    private array $defaultRow = [];

    private bool $keyed = false;

    private bool $includeRowId = false;

    private bool $definesColumns = false;

    private ?string $columnsFrom = null;

    public static function type(): string
    {
        return 'craft:editable-table-input';
    }

    /** @param list<EditableTableColumn> $columns */
    public function columns(array $columns): self
    {
        foreach ($columns as $column) {
            if (
                ! isset($column['key'], $column['label'], $column['type'])
                || ! is_string($column['key'])
                || ! is_string($column['label'])
                || ! is_string($column['type'])
            ) {
                throw new InvalidArgumentException(sprintf(
                    'Form Element %s columns require string key, label, and type values.',
                    self::type(),
                ));
            }

            if (! in_array($column['type'], self::COLUMN_TYPES, true)) {
                throw new InvalidArgumentException(sprintf(
                    'Form Element %s does not support the “%s” column type.',
                    self::type(),
                    $column['type'],
                ));
            }
        }

        $this->columns = $columns;

        return $this;
    }

    public function addRowLabel(string $addRowLabel): self
    {
        $this->addRowLabel = $addRowLabel;

        return $this;
    }

    /** @param array<string, mixed> $defaultRow */
    public function defaultRow(array $defaultRow): self
    {
        $this->defaultRow = $defaultRow;

        return $this;
    }

    public function keyed(bool $keyed = true): self
    {
        $this->keyed = $keyed;

        return $this;
    }

    public function includeRowId(bool $includeRowId = true): self
    {
        $this->includeRowId = $includeRowId;

        return $this;
    }

    public function definesColumns(bool $definesColumns = true): self
    {
        $this->definesColumns = $definesColumns;

        return $this;
    }

    public function columnsFrom(string $inputName): self
    {
        $this->columnsFrom = $inputName;

        return $this;
    }

    #[\Override]
    protected function props(): array
    {
        return array_filter([
            'columns' => $this->columns,
            'addRowLabel' => $this->addRowLabel,
            'defaultRow' => $this->defaultRow === [] ? null : $this->defaultRow,
            'keyed' => $this->keyed ?: null,
            'includeRowId' => $this->includeRowId ?: null,
            'definesColumns' => $this->definesColumns ?: null,
            'columnsFrom' => $this->columnsFrom,
        ], fn (mixed $value): bool => $value !== null);
    }
}
