<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use Closure;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Field\Contracts\CrossSiteCopyableFieldInterface;
use CraftCms\Cms\Field\Contracts\DefaultableFieldInterface;
use CraftCms\Cms\Field\Contracts\TableCellInterface;
use CraftCms\Cms\Field\Data\ColorData;
use CraftCms\Cms\Field\Models\Field as FieldModel;
use CraftCms\Cms\Field\TableCells\MissingTableCell;
use CraftCms\Cms\Field\TableCells\TableCellContext;
use CraftCms\Cms\Form\Contracts\Control;
use CraftCms\Cms\Form\Controls\Lightswitch;
use CraftCms\Cms\Form\Controls\Number;
use CraftCms\Cms\Form\Controls\Table as TableControl;
use CraftCms\Cms\Form\Controls\TableColumns;
use CraftCms\Cms\Form\Controls\Text;
use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Form\Nodes\Field as FormField;
use CraftCms\Cms\Form\Nodes\Group;
use CraftCms\Cms\Gql\GqlEntityRegistry;
use CraftCms\Cms\Gql\Types\Generators\TableRowType;
use CraftCms\Cms\Gql\Types\TableRow;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\DateTimeHelper;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Query;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Validation\Rules\HandleRule;
use DateTimeInterface;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use LogicException;
use Override;

use function CraftCms\Cms\t;

/**
 * Table represents a Table field.
 *
 * @phpstan-type TableColumnType string
 * @phpstan-type TableOption array{label: string, value: string, default?: bool}
 * @phpstan-type TableColumn array{heading: string, handle: string, type: TableColumnType, width?: int|string, options?: list<TableOption>, settings?: array<string, mixed>}
 * @phpstan-type TableCellValue bool|float|int|string|DateTimeInterface|ColorData|null
 * @phpstan-type TableRowData array<string, TableCellValue>
 *
 * @since 6.0.0
 */
class Table extends Field implements CrossSiteCopyableFieldInterface, DefaultableFieldInterface
{
    /** @var array<string, array<string, true>> */
    private array $columnErrors = [];

    #[Override]
    public static function displayName(): string
    {
        return t('Table');
    }

    #[Override]
    public static function icon(): string
    {
        return 'table';
    }

    #[Override]
    public static function phpType(): string
    {
        return 'array|null';
    }

    /** @return array<string, string> */
    private static function typeOptions(): array
    {
        $options = [];
        foreach (app(TableCellTypes::class)->selectableTypes() as $identity => $type) {
            $options[$identity] = $type::displayName();
        }
        asort($options);

        return $options;
    }

    /** @param array<string, mixed> $column */
    public function cellType(array $column): TableCellInterface
    {
        return app(TableCellTypes::class)->create($column);
    }

    public function hasMissingCellTypes(): bool
    {
        return array_any($this->columns, fn ($column) => $this->cellType($column) instanceof MissingTableCell);
    }

    #[Override]
    public static function dbType(): string
    {
        return Query::TYPE_JSON;
    }

    #[Override]
    public function formControl(FieldContext $context): Control
    {
        $columns = $this->controlColumns($context->element?->getLanguage());
        $missing = $this->hasMissingCellTypes();
        $value = $this->controlValues(is_array($context->value) ? $context->value : [], false);

        $control = TableControl::make($context->path)
            ->columns($columns)
            ->defaultValues($this->controlValues([$this->defaultRowValues])[0])
            ->allowAdd(! $this->staticRows)
            ->allowDelete(! $this->staticRows)
            ->allowReorder(! $this->staticRows)
            ->addRowLabel(t($this->addRowLabel, category: 'site'))
            ->includeRowId($this->staticRows)
            ->minRows($this->minRows)
            ->maxRows($this->maxRows)
            ->value($value);

        return $missing ? $control->mode(ControlMode::ReadOnly) : $control;
    }

    /**
     * @param  array<array<string, mixed>>  $rows
     * @return array<array<string, mixed>>
     */
    private function controlValues(array $rows, bool $normalize = true): array
    {
        if ($this->hasMissingCellTypes()) {
            return $rows;
        }

        $cells = array_map($this->cellType(...), $this->columns);
        foreach ($rows as &$row) {
            foreach ($cells as $id => $cell) {
                if (! array_key_exists($id, $row)) {
                    continue;
                }
                $value = $normalize ? $cell->normalizeValue($row[$id]) : $row[$id];
                $row[$id] = $cell->formControl(new TableCellContext([$id], $value))->getValue();
            }
        }

        return $rows;
    }

    /** @return array<string, array<string, mixed>> */
    private function controlColumns(?string $locale = null, bool $editableHeadings = false): array
    {
        $columns = [];
        foreach ($this->columns as $id => $column) {
            if ($editableHeadings && $column['type'] === 'heading') {
                $column['type'] = 'singleline';
            }
            $column['heading'] = t($column['heading'] ?? '', category: 'site', locale: $locale);
            $column['control'] = $this->cellType($column)->formControl(new TableCellContext('value', locale: $locale));
            $columns[$id] = $column;
        }

        return $columns;
    }

    #[Override]
    public function settingsForm(FormContext $context = new FormContext): Form
    {
        $columnForms = [];
        $types = [];
        foreach (self::typeOptions() as $identity => $label) {
            $types[] = ['value' => $identity, 'label' => $label];
        }
        foreach ($this->columns as $id => $column) {
            $form = $this->cellType($column)->settingsForm(new FormContext(values: $column));
            if ($form !== null) {
                $columnForms[$id] = $form;
            }
        }
        $defaultColumns = $this->controlColumns(editableHeadings: true);
        $columnsControl = TableColumns::make('columns')
            ->cellTypes($types)
            ->columnForms($columnForms)
            ->errors($this->columnErrors)
            ->value($this->columns)
            ->reactive();
        if ($this->hasMissingCellTypes()) {
            $columnsControl->mode(ControlMode::ReadOnly);
        }

        return Form::make([
            FormField::make(t('Columns'))
                ->instructions(t('Define the columns your table should have.'))
                ->control($columnsControl),
            Group::make('table-default-values', [
                FormField::make(t('Default Values'))
                    ->instructions(t('Define the default values for the field.'))
                    ->control(TableControl::make('defaults')
                        ->columns($defaultColumns)
                        ->allowAdd()
                        ->allowDelete()
                        ->allowReorder()
                        ->includeRowId($this->staticRows)
                        ->value($this->controlValues($this->defaults ?? []))
                        ->mode($this->hasMissingCellTypes() ? ControlMode::ReadOnly : ControlMode::Editable)),
                FormField::make(t('Default Row Values'))
                    ->instructions(t('Define the default values for new rows.'))
                    ->control(TableControl::make('defaultRowValues')
                        ->columns($defaultColumns)
                        ->minRows(1)
                        ->maxRows(1)
                        ->value($this->controlValues([$this->defaultRowValues]))
                        ->mode($this->hasMissingCellTypes() ? ControlMode::ReadOnly : ControlMode::Editable)),
            ])->dependsOn('settings.columns'),
            FormField::make(t('Static Rows'))
                ->instructions(t('Whether the table rows should be restricted to those defined by the “Default Values” setting.'))
                ->control(Lightswitch::make('staticRows')->value($this->staticRows)),
            FormField::make(t('Min Rows'))
                ->instructions(t('The minimum number of rows the field is allowed to have.'))
                ->control(Number::make('minRows')->min(0)->value($this->minRows)),
            FormField::make(t('Max Rows'))
                ->instructions(t('The maximum number of rows the field is allowed to have.'))
                ->control(Number::make('maxRows')->min(0)->value($this->maxRows)),
            FormField::make(t('Add Row Label'))
                ->instructions(t('Insert the button label for adding a new row to the table.'))
                ->control(Text::make('addRowLabel')->value($this->addRowLabel)),
        ]);
    }

    /**
     * @var bool Whether the rows should be static.
     */
    public bool $staticRows = false;

    /**
     * @var string|null Custom add row button label
     */
    public ?string $addRowLabel = null;

    /**
     * @var int|null Maximum number of Rows allowed
     */
    public ?int $maxRows = null;

    /**
     * @var int|null Minimum number of Rows allowed
     */
    public ?int $minRows = null;

    /** @var array<string, TableColumn> The columns that should be shown in the table */
    public array $columns = [
        'col1' => [
            'heading' => '',
            'handle' => '',
            'type' => 'singleline',
        ],
    ];

    /** @var list<TableRowData>|null The default row values that new elements should have */
    public ?array $defaults = [[]];

    /** @var TableRowData The default values for newly added rows */
    public array $defaultRowValues = [];

    public function __construct($config = [])
    {
        // Config normalization
        if (array_key_exists('columns', $config)) {
            if (! is_array($config['columns'])) {
                unset($config['columns']);
            } else {
                foreach ($config['columns'] as $colId => &$column) {
                    // If the column doesn't specify a type, then it probably wasn't meant to be submitted
                    if (! isset($column['type'])) {
                        unset($config['columns'][$colId]);

                        continue;
                    }

                    if ($column['type'] === 'select') {
                        if (! isset($column['options'])) {
                            $column['options'] = [];
                        } elseif (is_string($column['options'])) {
                            $column['options'] = Json::decode($column['options']);
                        }
                    }
                }
                unset($column);
            }
        }

        if (isset($config['defaults'])) {
            if (! is_array($config['defaults'])) {
                $config['defaults'] = (! empty($config['id']) || $config['defaults'] === '') ? [] : [[]];
            } else {
                // Make sure the array is non-associative and with incrementing keys
                $config['defaults'] = array_values($config['defaults']);
            }
        }

        if (isset($config['defaultRowValues'])) {
            $defaultRowValues = $config['defaultRowValues'];

            $config['defaultRowValues'] = match (true) {
                ! is_array($defaultRowValues) => [],
                count($defaultRowValues) === 1
                    && is_array($defaultRowValues[0] ?? null) => $defaultRowValues[0],
                default => $defaultRowValues,
            };
        }

        // handle some default cell values
        if (! empty($config['columns'])) {
            foreach ($config['columns'] as $colId => $col) {
                // Convert default date cell values to ISO8601 strings
                if (in_array($col['type'], ['date', 'time'], true)) {
                    if (isset($config['defaults'])) {
                        foreach ($config['defaults'] as &$row) {
                            if (isset($row[$colId])) {
                                $row[$colId] = DateTimeHelper::toIso8601($row[$colId]) ?: null;
                            }
                        }
                        unset($row);
                    }
                    if (isset($config['defaultRowValues'][$colId])) {
                        $config['defaultRowValues'][$colId] = DateTimeHelper::toIso8601($config['defaultRowValues'][$colId]) ?: null;
                    }
                }
            }
        }

        // remove unused settings
        unset($config['columnType']);

        parent::__construct($config);

        $this->addRowLabel ??= t('Add a row');

        if ($this->staticRows) {
            $this->minRows = null;
            $this->maxRows = null;
        }
    }

    #[Override]
    public function getRules(): array
    {
        return array_merge(parent::getRules(), [
            'minRows' => ['nullable', Rule::when(fn ($input) => $input->maxRows > 0, ['lte:maxRows']), 'integer', 'min:0'],
            'maxRows' => ['nullable', Rule::when(fn ($input) => $input->minRows > 0, ['gte:minRows']), 'integer', 'min:0'],
        ]);
    }

    public function afterValidate(?Validator $validator = null): void
    {
        $this->columnErrors = [];
        $typeOptions = self::typeOptions();

        $persistedColumns = null;
        foreach ($this->columns as $colId => &$col) {
            if (! isset($typeOptions[$col['type']])) {
                $persistedColumns ??= $this->id !== null
                    ? (FieldModel::query()->whereKey($this->id)->value('settings')['columns'] ?? [])
                    : [];
                if (($persistedColumns[$colId] ?? null) === $col) {
                    continue;
                }
                $this->columnErrors[$colId]['type'] = true;
                $validator?->errors()->add('columns', t('The selected table cell type is unavailable.'));
            } else {
                $cell = $this->cellType($col);
                if (! $cell->validate()) {
                    foreach ($cell->errors()->getMessages() as $attribute => $messages) {
                        $setting = explode('.', $attribute)[0];
                        $prefix = ! array_key_exists($setting, $col) && array_key_exists($setting, $col['settings'] ?? [])
                            ? "columns.{$colId}.settings"
                            : "columns.{$colId}";
                        $this->columnErrors[$colId][$attribute] = true;

                        foreach ($messages as $message) {
                            $validator?->errors()->add("{$prefix}.{$attribute}", $message);
                        }
                    }
                }
            }

            if (! $col['handle']) {
                continue;
            }

            $error = null;

            if (! preg_match('/^'.HandleRule::$handlePattern.'$/', (string) $col['handle'])) {
                $error = t('“{handle}” isn’t a valid handle.', [
                    'handle' => $col['handle'],
                ]);
            } elseif (preg_match('/^col\d+$/', (string) $col['handle'])) {
                $error = t('Column handles can’t be in the format “{format}”.', [
                    'format' => 'colX',
                ]);
            }

            if ($error) {
                $this->columnErrors[$colId]['handle'] = true;
                $validator?->errors()->add('columns', $error);
            }
        }
    }

    /**
     * @return bool whether minRows was set
     */
    public function hasMinRows(): bool
    {
        return (bool) $this->minRows;
    }

    /**
     * @return bool whether maxRows was set
     */
    public function hasMaxRows(): bool
    {
        return (bool) $this->maxRows;
    }

    #[Override]
    public function beforeSave(bool $isNew): bool
    {
        if (! parent::beforeSave($isNew)) {
            return false;
        }

        if ($this->staticRows && ! empty($this->defaults)) {
            // make sure the default rows have IDs assigned
            foreach ($this->defaults as &$row) {
                $row['rowId'] ??= Str::uuid()->toString();
            }
        }

        return true;
    }

    #[Override]
    public function useFieldset(): bool
    {
        return true;
    }

    /** @return list<TableRowData>|null */
    public function getDefaultValue(): ?array
    {
        return $this->defaults;
    }

    #[Override]
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        if ($this->columns === []) {
            return '';
        }

        $errors = array_filter(
            $element?->errors()->getMessages() ?? [],
            fn (string $attribute): bool => $attribute === $this->handle || str_starts_with($attribute, "$this->handle."),
            ARRAY_FILTER_USE_KEY,
        );
        $context = new FormContext(errors: $errors);
        $control = $this->formControl(new FieldContext(
            path: $this->handle,
            value: $value,
            element: $element,
            form: $context,
            inline: $inline,
        ));
        $payload = app(FormResolver::class)->resolve(
            Form::make([FormField::make()->control($control)]),
            $context,
        );

        return Html::tag('craft-table-form', '', [
            'id' => $this->getInputId(),
            'name' => $this->handle,
            'role' => 'group',
            'aria' => [
                'labelledby' => $this->getLabelId(),
                'describedby' => $this->describedBy,
            ],
            'data-payload' => Json::encode($payload),
        ]);
    }

    /** @return list<Closure> */
    #[Override]
    public function getElementRules(ElementInterface $element): array
    {
        return [
            fn (
                string $attribute,
                mixed $value,
                Closure $fail,
            ) => $this->validateTableData($value, $fail, $attribute),
        ];
    }

    /**
     * Validates the table data.
     */
    public function validateTableData(mixed $value, Closure $fail, ?string $attribute = null): void
    {
        if (empty($value)) {
            return;
        }

        if (empty($this->columns) || $this->hasMissingCellTypes()) {
            return;
        }

        $invalid = false;
        foreach ($value as $rowIndex => &$row) {
            foreach ($this->columns as $colId => $col) {
                if (is_string($row[$colId] ?? null)) {
                    // Trim the value before validating
                    $row[$colId] = trim($row[$colId]);
                }

                foreach ($this->cellErrors($col, $row[$colId] ?? null) as $message) {
                    $invalid = true;
                    if ($attribute === null) {
                        $fail($message);
                    } else {
                        $fail("$attribute.$rowIndex.$colId", $message);
                    }
                }
            }
        }

        if ($invalid && $attribute !== null) {
            $fail($attribute, t('One or more table cells contain invalid values.'));
        }
    }

    #[Override]
    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        return $this->_normalizeValueInternal($value, $element, false);
    }

    #[Override]
    public function normalizeValueFromRequest(mixed $value, ?ElementInterface $element): mixed
    {
        if ($this->hasMissingCellTypes()) {
            return $element?->getFieldValue($this->handle);
        }

        return $this->_normalizeValueInternal($value, $element, true);
    }

    /** @return list<TableRowData>|null */
    private function _normalizeValueInternal(mixed $value, ?ElementInterface $element, bool $fromRequest): ?array
    {
        if (empty($this->columns)) {
            return null;
        }

        if ($this->hasMissingCellTypes()) {
            $value = is_string($value) ? Json::decodeIfJson($value) : $value;

            return is_array($value) ? $value : null;
        }

        $defaults = $this->defaults ?? [];

        // Apply static translations
        foreach ($defaults as &$row) {
            foreach ($this->columns as $colId => $col) {
                if ($col['type'] === 'heading' && isset($row[$colId])) {
                    $row[$colId] = t($row[$colId], category: 'site', locale: $element?->getLanguage());
                }
            }
        }

        if (is_string($value) && ! empty($value)) {
            $value = Json::decodeIfJson($value);
        } elseif ($value === null && ($this->isFresh($element) || $this->staticRows)) {
            $value = $defaults;
        }

        if (! is_array($value)) {
            $value = [];
        }

        // Normalize the values and make them accessible from both the col IDs and the handles
        $value = array_values($value);

        if ($this->staticRows) {
            // get the order of the default rows
            $order = Arr::pluck($this->defaults ?? [], 'rowId');
            $missingValueRowIds = null;

            if (! empty($order)) {
                // if there's no rowIds, add them
                if (Arr::containsRecursive($value, 'rowId') === false) {
                    foreach ($value as $key => &$row) {
                        $row['rowId'] = $order[$key];
                    }
                }

                // the rowIds present in the $value array
                $usedValueRowIds = Arr::pluck($value, 'rowId');

                // if the field has a set order
                $missingValueRowIds = array_values(array_diff($order, $usedValueRowIds));
                $leftoverValueRowIds = array_diff($usedValueRowIds, $order);

                // if the rowId is missing from the defaults - remove it from the $value array
                foreach ($leftoverValueRowIds as $key => $rowId) {
                    unset($value[$key]);
                }
            }

            $valueRows = count($value);
            $totalRows = count($defaults);

            // if we have too few rows
            if ($valueRows < $totalRows) {
                if ($missingValueRowIds === null) {
                    $value = array_pad($value, $totalRows, []);
                } else {
                    // if we have the missing value rowIds - add them in places where settings rowId doesn't exist in the $value array
                    while (count($value) < $totalRows) {
                        $value[] = ['rowId' => reset($missingValueRowIds)];
                        array_shift($missingValueRowIds);
                    }
                }
            }

            if (! empty($order)) {
                // sort as per the field's settings
                usort($value, function ($a, $b) use ($order) {
                    $posA = array_search($a['rowId'], $order);
                    $posB = array_search($b['rowId'], $order);

                    return $posA - $posB;
                });
            }

            // now that we've sorted the rows, if we have too many rows - splice
            if ($valueRows > $totalRows) {
                array_splice($value, $totalRows);
            }
        }

        // If the value is still empty, return null
        if (empty($value)) {
            return null;
        }

        foreach ($value as $rowIndex => &$row) {
            foreach ($this->columns as $colId => $col) {
                if ($col['type'] === 'heading') {
                    $cellValue = $defaults[$rowIndex][$colId] ?? '';
                } elseif (array_key_exists($colId, $row)) {
                    $cellValue = $row[$colId];
                } elseif ($col['handle'] && array_key_exists((string) $col['handle'], $row)) {
                    $cellValue = $row[$col['handle']];
                } else {
                    $cellValue = null;
                }
                $cellValue = $this->cellType($col)->normalizeValue($cellValue, $fromRequest);
                $row[$colId] = $cellValue;
                if ($col['handle']) {
                    $row[$col['handle']] = $cellValue;
                }
            }
        }

        return $value;
    }

    #[Override]
    public function serializeValue(mixed $value, ?ElementInterface $element): mixed
    {
        if (! is_array($value) || empty($this->columns)) {
            return null;
        }

        if ($this->hasMissingCellTypes()) {
            return $value;
        }

        $serialized = [];
        $supportsMb4 = DB::supportsMb4();

        foreach ($value as $row) {
            $serializedRow = [];
            foreach ($this->columns as $colId => $column) {
                if ($column['type'] === 'heading') {
                    continue;
                }

                $value = $row[$colId] ?? null;

                if (is_string($value)) {
                    $value = Str::escapeShortcodes($value);
                    if (! $supportsMb4) {
                        $value = Str::emojiToShortcodes($value);
                    }
                }

                $serializedRow[$colId] = $this->cellType($column)->serializeValue($value);
            }
            $serialized[] = $serializedRow;
        }

        return $serialized;
    }

    #[Override]
    public function serializeValueForDb(mixed $value, ElementInterface $element): mixed
    {
        if (! is_array($value) || empty($this->columns)) {
            return null;
        }

        if ($this->hasMissingCellTypes()) {
            return $value;
        }

        $serialized = [];
        $supportsMb4 = DB::supportsMb4();

        foreach ($value as $row) {
            $serializedRow = [];
            foreach ($this->columns as $colId => $column) {
                if ($column['type'] === 'heading') {
                    continue;
                }

                $value = $row[$colId] ?? null;

                if (is_string($value) && ! $supportsMb4) {
                    $value = Str::emojiToShortcodes(Str::escapeShortcodes($value));
                }

                $serializedRow[$colId] = $this->cellType($column)->serializeValue($value, true);
            }

            // if the table has static rows, store the rowId too
            if ($this->staticRows) {
                if (isset($row['rowId'])) {
                    $serializedRow['rowId'] = $row['rowId'];
                }
            }

            $serialized[] = $serializedRow;
        }

        return $serialized;
    }

    #[Override]
    protected function searchKeywords(mixed $value, ElementInterface $element): string
    {
        if (! is_array($value) || empty($this->columns)) {
            return '';
        }

        $keywords = [];

        foreach ($value as $row) {
            foreach ($this->columns as $colId => $column) {
                if (! $this->cellType($column) instanceof MissingTableCell) {
                    $keywords[] = $this->cellType($column)->searchKeywords($row[$colId] ?? null);
                }
            }
        }

        return implode(' ', $keywords);
    }

    #[Override]
    public function getContentGqlType(): Type
    {
        $type = TableRowType::generateType($this);

        return Type::listOf($type);
    }

    #[Override]
    public function getContentGqlMutationArgumentType(): Type
    {
        $typeName = $this->handle.'_TableRowInput';

        $type = GqlEntityRegistry::getOrCreate($typeName, fn () => new InputObjectType([
            'name' => $typeName,
            'description' => sprintf('Defines a row within the “%s” Table field’s data.', $this->name),
            'fields' => fn () => TableRow::prepareRowFieldDefinition($this->columns, input: true),
        ]));

        if (! $type instanceof InputObjectType) {
            throw new LogicException("The $typeName GraphQL entity must be an input object type.");
        }

        return Type::listOf($type);
    }

    /**
     * @param  array<string, mixed>  $column
     * @return list<string>
     */
    private function cellErrors(array $column, mixed $value): array
    {
        $cell = $this->cellType($column);
        $rules = $cell->getValueRules();
        if ($rules === []) {
            return [];
        }

        return ValidatorFacade::make(
            ['value' => $cell->serializeValue($value)],
            ['value' => $rules],
        )->errors()->get('value');
    }
}
