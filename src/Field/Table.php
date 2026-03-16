<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use Closure;
use Craft;
use craft\base\ElementInterface;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;
use craft\helpers\DateTimeHelper;
use craft\web\assets\tablesettings\TableSettingsAsset;
use craft\web\assets\timepicker\TimepickerAsset;
use CraftCms\Cms\Field\Contracts\CrossSiteCopyableFieldInterface;
use CraftCms\Cms\Field\Data\ColorData;
use CraftCms\Cms\Gql\GqlEntityRegistry;
use CraftCms\Cms\Gql\Types\Generators\TableRowType;
use CraftCms\Cms\Gql\Types\TableRow;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Query;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Validation\Rules\ColorRule;
use CraftCms\Cms\Validation\Rules\HandleRule;
use DateTime;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Override;
use yii\db\Schema;

use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

/**
 * Table represents a Table field.
 */
class Table extends Field implements CrossSiteCopyableFieldInterface
{
    private static array $typeOptions;

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

    private static function typeOptions(): array
    {
        if (! isset(self::$typeOptions)) {
            self::$typeOptions = [
                'checkbox' => t('Checkbox'),
                'color' => t('Color'),
                'date' => t('Date'),
                'select' => t('Dropdown'),
                'email' => t('Email'),
                'heading' => t('Row heading'),
                'lightswitch' => t('Lightswitch'),
                'multiline' => t('Multi-line text'),
                'number' => t('Number'),
                'singleline' => t('Single-line text'),
                'time' => t('Time'),
                'url' => t('URL'),
            ];

            // Make sure they are sorted alphabetically (post-translation)
            asort(self::$typeOptions);
        }

        return self::$typeOptions;
    }

    #[Override]
    public static function dbType(): string
    {
        return Schema::TYPE_JSON;
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

    /**
     * @var array The columns that should be shown in the table
     */
    public array $columns = [
        'col1' => [
            'heading' => '',
            'handle' => '',
            'type' => 'singleline',
        ],
    ];

    /**
     * @var array|null The default row values that new elements should have
     */
    public ?array $defaults = [[]];

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
                    } else {
                        unset($column['options']);
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

        // handle some default cell values
        if (! empty($config['columns']) && isset($config['defaults'])) {
            foreach ($config['columns'] as $colId => $col) {
                // Convert default date cell values to ISO8601 strings
                if (in_array($col['type'], ['date', 'time'], true)) {
                    foreach ($config['defaults'] as &$row) {
                        if (isset($row[$colId])) {
                            $row[$colId] = DateTimeHelper::toIso8601($row[$colId]) ?: null;
                        }
                    }
                }
            }
        }

        // remove unused settings
        unset($config['columnType']);

        parent::__construct($config);

        if (! isset($this->addRowLabel)) {
            $this->addRowLabel = t('Add a row');
        }

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
        $typeOptions = self::typeOptions();

        foreach ($this->columns as &$col) {
            if (! isset($typeOptions[$col['type']])) {
                $col['type'] = 'singleline';
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
                $col['handle'] = [
                    'value' => $col['handle'],
                    'hasErrors' => true,
                ];

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

    public function getSettingsHtml(): string
    {
        return $this->settingsHtml(false);
    }

    #[Override]
    public function getReadOnlySettingsHtml(): string
    {
        return $this->settingsHtml(true);
    }

    private function settingsHtml(bool $readOnly): string
    {
        $columnSettings = [
            'heading' => [
                'heading' => t('Column Heading'),
                'type' => 'singleline',
                'autopopulate' => 'handle',
            ],
            'handle' => [
                'heading' => t('Handle'),
                'code' => true,
                'type' => 'singleline',
            ],
            'width' => [
                'heading' => t('Width'),
                'code' => true,
                'type' => 'singleline',
                'width' => 50,
            ],
            'type' => [
                'heading' => t('Type'),
                'class' => 'thin',
                'type' => 'select',
                'options' => self::typeOptions(),
            ],
        ];

        $dropdownSettingsCols = [
            'label' => [
                'heading' => t('Option Label'),
                'type' => 'singleline',
                'autopopulate' => 'value',
                'class' => 'option-label',
            ],
            'value' => [
                'heading' => t('Value'),
                'type' => 'singleline',
                'class' => 'option-value code',
            ],
            'default' => [
                'heading' => t('Default?'),
                'type' => 'checkbox',
                'radioMode' => true,
                'class' => 'option-default thin',
            ],
        ];

        $dropdownSettingsHtml = Cp::editableTableFieldHtml([
            'label' => t('Dropdown Options'),
            'instructions' => t('Define the available options.'),
            'id' => '__ID__',
            'name' => '__NAME__',
            'addRowLabel' => t('Add an option'),
            'allowAdd' => true,
            'allowReorder' => true,
            'allowDelete' => true,
            'cols' => $dropdownSettingsCols,
            'initJs' => false,
        ]);

        // Replace heading columns with singleline, for the Default Values table
        $columns = array_map(function (array $column) {
            if ($column['type'] === 'heading') {
                $column['type'] = 'singleline';
                $column['class'] = 'heading';
            }

            return $column;
        }, $this->columns);

        Craft::$app->getView()->registerAssetBundle(TimepickerAsset::class);
        Craft::$app->getView()->registerAssetBundle(TableSettingsAsset::class);
        HtmlStack::js('new Craft.TableFieldSettings('.
            Json::encode(InputNamespace::namespaceInputName('columns')).', '.
            Json::encode(InputNamespace::namespaceInputName('defaults')).', '.
            Json::encode($columns).', '.
            Json::encode($this->defaults ?? []).', '.
            Json::encode($columnSettings).', '.
            Json::encode($dropdownSettingsHtml).', '.
            Json::encode($dropdownSettingsCols).', '.
            Json::encode($this->staticRows).', '.
            ');');

        $columnsField = template('_components/fieldtypes/Table/columntable', [
            'cols' => $columnSettings,
            'rows' => $this->columns,
            'errors' => $this->errors()->get('columns'),
            'readOnly' => $readOnly,
        ]);

        $defaultsField = Cp::editableTableFieldHtml([
            'label' => t('Default Values'),
            'instructions' => t('Define the default values for the field.'),
            'id' => 'defaults',
            'name' => 'defaults',
            'allowAdd' => true,
            'allowReorder' => true,
            'allowDelete' => true,
            'cols' => $columns,
            'rows' => array_map(function (array $row) {
                // make sure the row has a UUID
                $row['rowId'] ??= Str::uuid()->toString();

                return $row;
            }, $this->defaults ?? []),
            'initJs' => false,
            'static' => $readOnly,
            'includeRowId' => true,
        ]);

        return template('_components/fieldtypes/Table/settings', [
            'field' => $this,
            'columnsField' => $columnsField,
            'defaultsField' => $defaultsField,
            'readOnly' => $readOnly,
        ]);
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

    #[Override]
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        Craft::$app->getView()->registerAssetBundle(TimepickerAsset::class);

        return $this->_getInputHtml($value, $element, false);
    }

    #[Override]
    public function getElementRules(ElementInterface $element): array
    {
        return [
            fn (
                string $attribute,
                mixed $value,
                Closure $fail,
            ) => $this->validateTableData($value, $fail),
        ];
    }

    /**
     * Validates the table data.
     */
    public function validateTableData(mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            return;
        }

        if (empty($this->columns)) {
            return;
        }

        foreach ($value as &$row) {
            foreach ($this->columns as $colId => $col) {
                if (is_string($row[$colId])) {
                    // Trim the value before validating
                    $row[$colId] = trim($row[$colId]);
                }

                if (! $this->_validateCellValue($col['type'], $row[$colId], $error)) {
                    $fail($error);
                }
            }
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
        return $this->_normalizeValueInternal($value, $element, true);
    }

    private function _normalizeValueInternal(mixed $value, ?ElementInterface $element, bool $fromRequest): ?array
    {
        if (empty($this->columns)) {
            return null;
        }

        $defaults = $this->defaults ?? [];

        // Apply static translations
        foreach ($defaults as &$row) {
            foreach ($this->columns as $colId => $col) {
                if ($col['type'] === 'heading' && isset($row[$colId])) {
                    $row[$colId] = t($row[$colId], category: 'site');
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
            $order = ArrayHelper::getColumn($this->defaults ?? [], 'rowId');
            $missingValueRowIds = null;

            if (! empty($order)) {
                // if there's no rowIds, add them
                if (ArrayHelper::containsRecursive($value, 'rowId') === false) {
                    foreach ($value as $key => &$row) {
                        $row['rowId'] = $order[$key];
                    }
                }

                // the rowIds present in the $value array
                $usedValueRowIds = ArrayHelper::getColumn($value, 'rowId');

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
                $cellValue = $this->_normalizeCellValue($col['type'], $cellValue, $fromRequest);
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

        $serialized = [];
        $supportsMb4 = Craft::$app->getDb()->getSupportsMb4();

        foreach ($value as $row) {
            $serializedRow = [];
            foreach ($this->columns as $colId => $column) {
                if ($column['type'] === 'heading') {
                    continue;
                }

                $value = $row[$colId];

                if (is_string($value)) {
                    $value = Str::escapeShortcodes($value);
                    if (! $supportsMb4) {
                        $value = Str::emojiToShortcodes($value);
                    }
                }

                $serializedRow[$colId] = parent::serializeValue($value ?? null, null);
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

        $serialized = [];
        $supportsMb4 = Craft::$app->getDb()->getSupportsMb4();

        foreach ($value as $row) {
            $serializedRow = [];
            foreach ($this->columns as $colId => $column) {
                if ($column['type'] === 'heading') {
                    continue;
                }

                $value = $row[$colId];

                if (is_string($value) && ! $supportsMb4) {
                    $value = Str::emojiToShortcodes(Str::escapeShortcodes($value));
                }

                // can't call parent::serializeValueForDb() here because that calls $this->serializeValue()
                // see https://github.com/craftcms/cms/pull/17091
                if ($value instanceof DateTime || DateTimeHelper::isIso8601($value)) {
                    $serializedRow[$colId] = Query::prepareDateForDb($value);
                } else {
                    $serializedRow[$colId] = parent::serializeValue($value, $element);
                }
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
            foreach (array_keys($this->columns) as $colId) {
                if (isset($row[$colId]) && ! $row[$colId] instanceof DateTime) {
                    $keywords[] = $row[$colId];
                }
            }
        }

        return implode(' ', $keywords);
    }

    #[Override]
    public function getStaticHtml(mixed $value, ElementInterface $element): string
    {
        return $this->_getInputHtml($value, $element, true);
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

        return Type::listOf(GqlEntityRegistry::getOrCreate($typeName, fn () => new InputObjectType([
            'name' => $typeName,
            'description' => sprintf('Defines a row within the “%s” Table field’s data.', $this->name),
            'fields' => fn () => TableRow::prepareRowFieldDefinition($this->columns),
        ])));
    }

    /**
     * Normalizes a cell’s value.
     *
     * @param  string  $type  The cell type
     * @param  mixed  $value  The cell value
     *
     * @see normalizeValue()
     */
    private function _normalizeCellValue(string $type, mixed $value, bool $fromRequest): mixed
    {
        switch ($type) {
            case 'color':
                if ($value instanceof ColorData) {
                    return $value;
                }

                if (! $value || $value === '#') {
                    return null;
                }

                $value = strtolower((string) $value);

                if ($value[0] !== '#') {
                    $value = '#'.$value;
                }

                if (strlen($value) === 4) {
                    $value = '#'.$value[1].$value[1].$value[2].$value[2].$value[3].$value[3];
                }

                return new ColorData($value);

            case 'multiline':
            case 'singleline':
                if ($value !== null) {
                    if (! $fromRequest) {
                        $value = Str::unescapeShortcodes(Str::shortcodesToEmoji($value));
                    }

                    return trim(Str::convertLineBreaks($value));
                }
                // no break
            case 'date':
            case 'time':
                return DateTimeHelper::toDateTime($value) ?: null;
        }

        return $value;
    }

    /**
     * Validates a cell’s value.
     *
     * @param  string  $type  The cell type
     * @param  mixed  $value  The cell value
     * @param  string|null  $error  The error text to set on the element
     * @return bool Whether the value is valid
     *
     * @see normalizeValue()
     */
    private function _validateCellValue(string $type, mixed $value, ?string &$error = null): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        switch ($type) {
            case 'color':
                /** @var ColorData $value */
                $value = $value->getHex();
                $validator = ValidatorFacade::make(
                    data: ['value' => $value],
                    rules: ['value' => new ColorRule]
                );
                break;
            case 'url':
                $validator = ValidatorFacade::make(
                    data: ['value' => $value],
                    rules: ['value' => ['url']],
                );
                break;
            case 'email':
                $validator = ValidatorFacade::make(
                    data: ['value' => $value],
                    rules: ['value' => ['email']],
                );
                break;
            default:
                return true;
        }

        if ($validator->fails()) {
            $error = $validator->errors()->first();
        }

        return $validator->passes();
    }

    /**
     * Returns the field's input HTML.
     */
    private function _getInputHtml(mixed $value, ?ElementInterface $element, bool $static): string
    {
        if (empty($this->columns)) {
            return '';
        }

        // Translate the column headings and dropdown option labels
        foreach ($this->columns as &$column) {
            if (! empty($column['heading'])) {
                $column['heading'] = t($column['heading'], category: 'site');
            }
            if (! empty($column['options'])) {
                array_walk($column['options'], function (&$option) {
                    $option['label'] = t($option['label'], category: 'site');
                });
            }
        }
        unset($column);

        if (! is_array($value)) {
            $value = [];
        }

        // Explicitly set each cell value to an array with a 'value' key
        $checkForErrors = $element && $element->errors()->has($this->handle);
        foreach ($value as &$row) {
            foreach ($this->columns as $colId => $col) {
                if (isset($row[$colId])) {
                    $hasErrors = $checkForErrors && ! $this->_validateCellValue($col['type'], $row[$colId]);
                    $row[$colId] = [
                        'value' => match ($col['type']) {
                            'heading' => Html::encode($row[$colId]),
                            default => $row[$colId],
                        },
                        'hasErrors' => $hasErrors,
                    ];
                }
            }
        }
        unset($row);

        // Make sure the value contains at least the minimum number of rows
        if ($this->minRows) {
            for ($i = count($value); $i < $this->minRows; $i++) {
                $value[] = [];
            }
        }

        return template('_includes/forms/editableTable', [
            'id' => $this->getInputId(),
            'name' => $this->handle,
            'cols' => $this->columns,
            'rows' => $value,
            'minRows' => $this->minRows,
            'maxRows' => $this->maxRows,
            'static' => $static,
            'staticRows' => $this->staticRows,
            'allowAdd' => true,
            'allowDelete' => true,
            'allowReorder' => true,
            'addRowLabel' => t($this->addRowLabel, category: 'site'),
            'describedBy' => $this->describedBy,
            'includeRowId' => $this->staticRows,
        ]);
    }
}
