<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields;

use Craft;
use craft\base\CrossSiteCopyableFieldInterface;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\fields\data\ColorData;
use craft\gql\GqlEntityRegistry;
use craft\gql\types\generators\TableRowType;
use craft\gql\types\TableRow;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\validators\ColorValidator;
use craft\validators\HandleValidator;
use craft\validators\UrlValidator;
use craft\web\assets\tablesettings\TableSettingsAsset;
use craft\web\assets\timepicker\TimepickerAsset;
use DateTime;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;
use yii\db\Schema;
use yii\validators\EmailValidator;

/**
 * Table represents a Table field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Table extends Field implements CrossSiteCopyableFieldInterface
{
    private static array $typeOptions;

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Table');
    }

    /**
     * @inheritdoc
     */
    public static function icon(): string
    {
        return 'table';
    }

    /**
     * @inheritdoc
     */
    public static function phpType(): string
    {
        return 'array|null';
    }

    private static function typeOptions(): array
    {
        if (!isset(self::$typeOptions)) {
            self::$typeOptions = [
                'checkbox' => Craft::t('app', 'Checkbox'),
                'color' => Craft::t('app', 'Color'),
                'date' => Craft::t('app', 'Date'),
                'select' => Craft::t('app', 'Dropdown'),
                'email' => Craft::t('app', 'Email'),
                'heading' => Craft::t('app', 'Row heading'),
                'lightswitch' => Craft::t('app', 'Lightswitch'),
                'multiline' => Craft::t('app', 'Multi-line text'),
                'number' => Craft::t('app', 'Number'),
                'singleline' => Craft::t('app', 'Single-line text'),
                'time' => Craft::t('app', 'Time'),
                'url' => Craft::t('app', 'URL'),
            ];

            // Make sure they are sorted alphabetically (post-translation)
            asort(self::$typeOptions);
        }

        return self::$typeOptions;
    }

    /**
     * @inheritdoc
     */
    public static function dbType(): array|string|null
    {
        return Schema::TYPE_JSON;
    }

    /**
     * @var bool Whether the rows should be static.
     * @since 4.5.0
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

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        // Config normalization
        if (array_key_exists('columns', $config)) {
            if (!is_array($config['columns'])) {
                unset($config['columns']);
            } else {
                foreach ($config['columns'] as $colId => &$column) {
                    // If the column doesn't specify a type, then it probably wasn't meant to be submitted
                    if (!isset($column['type'])) {
                        unset($config['columns'][$colId]);
                        continue;
                    }

                    if ($column['type'] === 'select') {
                        if (!isset($column['options'])) {
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
            if (!is_array($config['defaults'])) {
                $config['defaults'] = (!empty($config['id']) || $config['defaults'] === '') ? [] : [[]];
            } else {
                // Make sure the array is non-associative and with incrementing keys
                $config['defaults'] = array_values($config['defaults']);
            }
        }

        // handle some default cell values
        if (!empty($config['columns']) && isset($config['defaults'])) {
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
    }

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        if (!isset($this->addRowLabel)) {
            $this->addRowLabel = Craft::t('app', 'Add a row');
        }

        if ($this->staticRows) {
            $this->minRows = null;
            $this->maxRows = null;
        }
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['minRows'], 'compare', 'compareAttribute' => 'maxRows', 'operator' => '<=', 'type' => 'number', 'when' => [$this, 'hasMaxRows']];
        $rules[] = [['maxRows'], 'compare', 'compareAttribute' => 'minRows', 'operator' => '>=', 'type' => 'number', 'when' => [$this, 'hasMinRows']];
        $rules[] = [['minRows', 'maxRows'], 'integer', 'min' => 0];
        $rules[] = [['columns'], 'validateColumns'];
        return $rules;
    }

    /**
     * Validates the column configs.
     */
    public function validateColumns(): void
    {
        $typeOptions = self::typeOptions();

        foreach ($this->columns as &$col) {
            if (!isset($typeOptions[$col['type']])) {
                $col['type'] = 'singleline';
            }

            if ($col['handle']) {
                $error = null;

                if (!preg_match('/^' . HandleValidator::$handlePattern . '$/', $col['handle'])) {
                    $error = Craft::t('app', '“{handle}” isn’t a valid handle.', [
                        'handle' => $col['handle'],
                    ]);
                } elseif (preg_match('/^col\d+$/', $col['handle'])) {
                    $error = Craft::t('app', 'Column handles can’t be in the format “{format}”.', [
                        'format' => 'colX',
                    ]);
                }

                if ($error) {
                    $col['handle'] = [
                        'value' => $col['handle'],
                        'hasErrors' => true,
                    ];
                    $this->addError('columns', $error);
                }
            }
        }
    }

    /**
     * @return bool whether minRows was set
     */
    public function hasMinRows(): bool
    {
        return (bool)$this->minRows;
    }

    /**
     * @return bool whether maxRows was set
     */
    public function hasMaxRows(): bool
    {
        return (bool)$this->maxRows;
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml(): ?string
    {
        return $this->settingsHtml(false);
    }

    /**
     * @inheritdoc
     */
    public function getReadOnlySettingsHtml(): ?string
    {
        return $this->settingsHtml(true);
    }

    private function settingsHtml(bool $readOnly): string
    {
        $columnSettings = [
            'heading' => [
                'heading' => Craft::t('app', 'Column Heading'),
                'type' => 'singleline',
                'autopopulate' => 'handle',
            ],
            'handle' => [
                'heading' => Craft::t('app', 'Handle'),
                'code' => true,
                'type' => 'singleline',
            ],
            'width' => [
                'heading' => Craft::t('app', 'Width'),
                'code' => true,
                'type' => 'singleline',
                'width' => 50,
            ],
            'type' => [
                'heading' => Craft::t('app', 'Type'),
                'class' => 'thin',
                'type' => 'select',
                'options' => self::typeOptions(),
            ],
        ];

        $dropdownSettingsCols = [
            'label' => [
                'heading' => Craft::t('app', 'Option Label'),
                'type' => 'singleline',
                'autopopulate' => 'value',
                'class' => 'option-label',
            ],
            'value' => [
                'heading' => Craft::t('app', 'Value'),
                'type' => 'singleline',
                'class' => 'option-value code',
            ],
            'default' => [
                'heading' => Craft::t('app', 'Default?'),
                'type' => 'checkbox',
                'radioMode' => true,
                'class' => 'option-default thin',
            ],
        ];

        $dropdownSettingsHtml = Cp::editableTableFieldHtml([
            'label' => Craft::t('app', 'Dropdown Options'),
            'instructions' => Craft::t('app', 'Define the available options.'),
            'id' => '__ID__',
            'name' => '__NAME__',
            'addRowLabel' => Craft::t('app', 'Add an option'),
            'allowAdd' => true,
            'allowReorder' => true,
            'allowDelete' => true,
            'cols' => $dropdownSettingsCols,
            'initJs' => false,
        ]);

        // Replace heading columns with singleline, for the Default Values table
        $columns = array_map(function(array $column) {
            if ($column['type'] === 'heading') {
                $column['type'] = 'singleline';
                $column['class'] = 'heading';
            }
            return $column;
        }, $this->columns);

        $view = Craft::$app->getView();

        $view->registerAssetBundle(TimepickerAsset::class);
        $view->registerAssetBundle(TableSettingsAsset::class);
        $view->registerJs('new Craft.TableFieldSettings(' .
            Json::encode($view->namespaceInputName('columns')) . ', ' .
            Json::encode($view->namespaceInputName('defaults')) . ', ' .
            Json::encode($columns) . ', ' .
            Json::encode($this->defaults ?? []) . ', ' .
            Json::encode($columnSettings) . ', ' .
            Json::encode($dropdownSettingsHtml) . ', ' .
            Json::encode($dropdownSettingsCols) . ', ' .
            Json::encode($this->staticRows) . ', ' .
            ');');

        $columnsField = $view->renderTemplate('_components/fieldtypes/Table/columntable.twig', [
            'cols' => $columnSettings,
            'rows' => $this->columns,
            'errors' => $this->getErrors('columns'),
            'readOnly' => $readOnly,
        ]);

        $defaultsField = Cp::editableTableFieldHtml([
            'label' => Craft::t('app', 'Default Values'),
            'instructions' => Craft::t('app', 'Define the default values for the field.'),
            'id' => 'defaults',
            'name' => 'defaults',
            'allowAdd' => true,
            'allowReorder' => true,
            'allowDelete' => true,
            'cols' => $columns,
            'rows' => array_map(function(array $row) {
                // make sure the row has a UUID
                $row['rowId'] ??= StringHelper::uuid();
                return $row;
            }, $this->defaults),
            'initJs' => false,
            'static' => $readOnly,
            'includeRowId' => true,
        ]);

        return $view->renderTemplate('_components/fieldtypes/Table/settings.twig', [
            'field' => $this,
            'columnsField' => $columnsField,
            'defaultsField' => $defaultsField,
            'readOnly' => $readOnly,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function beforeSave(bool $isNew): bool
    {
        if (!parent::beforeSave($isNew)) {
            return false;
        }

        if ($this->staticRows && !empty($this->defaults)) {
            // make sure the default rows have IDs assigned
            foreach ($this->defaults as &$row) {
                $row['rowId'] ??= StringHelper::UUID();
            }
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function useFieldset(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        Craft::$app->getView()->registerAssetBundle(TimepickerAsset::class);
        return $this->_getInputHtml($value, $element, false);
    }

    /**
     * @inheritdoc
     */
    public function getElementValidationRules(): array
    {
        return ['validateTableData'];
    }

    /**
     * Validates the table data.
     *
     * @param ElementInterface $element
     */
    public function validateTableData(ElementInterface $element): void
    {
        $value = $element->getFieldValue($this->handle);

        if (!empty($value) && !empty($this->columns)) {
            foreach ($value as &$row) {
                foreach ($this->columns as $colId => $col) {
                    if (is_string($row[$colId])) {
                        // Trim the value before validating
                        $row[$colId] = trim($row[$colId]);
                    }

                    if (!$this->_validateCellValue($col['type'], $row[$colId], $error)) {
                        $element->addError($this->handle, $error);
                    }
                }
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        return $this->_normalizeValueInternal($value, $element, false);
    }

    /**
     * @inheritdoc
     */
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
                    $row[$colId] = Craft::t('site', $row[$colId]);
                }
            }
        }

        if (is_string($value) && !empty($value)) {
            $value = Json::decodeIfJson($value);
        } elseif ($value === null && ($this->isFresh($element) || $this->staticRows)) {
            $value = $defaults;
        }

        if (!is_array($value)) {
            $value = [];
        }

        // Normalize the values and make them accessible from both the col IDs and the handles
        $value = array_values($value);

        if ($this->staticRows) {
            // get the order of the default rows
            $order = ArrayHelper::getColumn($this->defaults, 'rowId');
            $missingValueRowIds = null;

            if (!empty($order)) {
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
                if (!empty($leftoverValueRowIds)) {
                    foreach ($leftoverValueRowIds as $key => $rowId) {
                        unset($value[$key]);
                    }
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

            if (!empty($order)) {
                // sort as per the field's settings
                usort($value, function($a, $b) use ($order) {
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
                } elseif ($col['handle'] && array_key_exists($col['handle'], $row)) {
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

    /**
     * @inheritdoc
     */
    public function serializeValue(mixed $value, ?ElementInterface $element): mixed
    {
        if (!is_array($value) || empty($this->columns)) {
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
                    $value = StringHelper::escapeShortcodes($value);
                    if (!$supportsMb4) {
                        $value = StringHelper::emojiToShortcodes($value);
                    }
                }

                $serializedRow[$colId] = parent::serializeValue($value ?? null, null);
            }
            $serialized[] = $serializedRow;
        }

        return $serialized;
    }

    /**
     * @inheritdoc
     */
    public function serializeValueForDb(mixed $value, ElementInterface $element): mixed
    {
        if (!is_array($value) || empty($this->columns)) {
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

                if (is_string($value) && !$supportsMb4) {
                    $value = StringHelper::emojiToShortcodes(StringHelper::escapeShortcodes($value));
                }

                // can't call parent::serializeValueForDb() here because that calls $this->serializeValue()
                // see https://github.com/craftcms/cms/pull/17091
                if ($value instanceof DateTime || DateTimeHelper::isIso8601($value)) {
                    $serializedRow[$colId] = Db::prepareDateForDb($value);
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

    /**
     * @inheritdoc
     */
    protected function searchKeywords(mixed $value, ElementInterface $element): string
    {
        if (!is_array($value) || empty($this->columns)) {
            return '';
        }

        $keywords = [];

        foreach ($value as $row) {
            foreach (array_keys($this->columns) as $colId) {
                if (isset($row[$colId]) && !$row[$colId] instanceof DateTime) {
                    $keywords[] = $row[$colId];
                }
            }
        }

        return implode(' ', $keywords);
    }

    /**
     * @inheritdoc
     */
    public function getStaticHtml(mixed $value, ElementInterface $element): string
    {
        return $this->_getInputHtml($value, $element, true);
    }

    /**
     * @inheritdoc
     * @since 3.3.0
     */
    public function getContentGqlType(): Type|array
    {
        $type = TableRowType::generateType($this);
        return Type::listOf($type);
    }

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    public function getContentGqlMutationArgumentType(): Type|array
    {
        $typeName = $this->handle . '_TableRowInput';

        return Type::listOf(GqlEntityRegistry::getOrCreate($typeName, fn() => new InputObjectType([
            'name' => $typeName,
            'description' => sprintf('Defines a row within the “%s” Table field’s data.', $this->name),
            'fields' => fn() => TableRow::prepareRowFieldDefinition($this->columns),
        ])));
    }

    /**
     * Normalizes a cell’s value.
     *
     * @param string $type The cell type
     * @param mixed $value The cell value
     * @param bool $fromRequest
     * @return mixed
     * @see normalizeValue()
     */
    private function _normalizeCellValue(string $type, mixed $value, bool $fromRequest): mixed
    {
        switch ($type) {
            case 'color':
                if ($value instanceof ColorData) {
                    return $value;
                }

                if (!$value || $value === '#') {
                    return null;
                }

                $value = strtolower($value);

                if ($value[0] !== '#') {
                    $value = '#' . $value;
                }

                if (strlen($value) === 4) {
                    $value = '#' . $value[1] . $value[1] . $value[2] . $value[2] . $value[3] . $value[3];
                }

                return new ColorData($value);

            case 'multiline':
            case 'singleline':
                if ($value !== null) {
                    if (!$fromRequest) {
                        $value = StringHelper::unescapeShortcodes(StringHelper::shortcodesToEmoji($value));
                    }
                    return trim(StringHelper::convertLineBreaks($value));
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
     * @param string $type The cell type
     * @param mixed $value The cell value
     * @param string|null $error The error text to set on the element
     * @return bool Whether the value is valid
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
                $validator = new ColorValidator();
                break;
            case 'url':
                $validator = new UrlValidator();
                break;
            case 'email':
                $validator = new EmailValidator();
                break;
            default:
                return true;
        }

        $validator->message = str_replace('{attribute}', '{value}', $validator->message);
        return $validator->validate($value, $error);
    }

    /**
     * Returns the field's input HTML.
     *
     * @param mixed $value
     * @param ElementInterface|null $element
     * @param bool $static
     * @return string
     */
    private function _getInputHtml(mixed $value, ?ElementInterface $element, bool $static): string
    {
        if (empty($this->columns)) {
            return '';
        }

        // Translate the column headings and dropdown option labels
        foreach ($this->columns as &$column) {
            if (!empty($column['heading'])) {
                $column['heading'] = Craft::t('site', $column['heading']);
            }
            if (!empty($column['options'])) {
                array_walk($column['options'], function(&$option) {
                    $option['label'] = Craft::t('site', $option['label']);
                });
            }
        }
        unset($column);

        if (!is_array($value)) {
            $value = [];
        }

        // Explicitly set each cell value to an array with a 'value' key
        $checkForErrors = $element && $element->hasErrors($this->handle);
        foreach ($value as &$row) {
            foreach ($this->columns as $colId => $col) {
                if (isset($row[$colId])) {
                    $hasErrors = $checkForErrors && !$this->_validateCellValue($col['type'], $row[$colId]);
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

        return Craft::$app->getView()->renderTemplate('_includes/forms/editableTable.twig', [
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
            'addRowLabel' => Craft::t('site', $this->addRowLabel),
            'describedBy' => $this->describedBy,
            'includeRowId' => $this->staticRows,
        ]);
    }
}
