<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\fields\data\ColorData;
use craft\gql\GqlEntityRegistry;
use craft\gql\types\generators\TableRowType as TableRowTypeGenerator;
use craft\gql\types\TableRow;
use craft\helpers\Cp;
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;
use craft\validators\ColorValidator;
use craft\validators\HandleValidator;
use craft\validators\UrlValidator;
use craft\web\assets\tablesettings\TableSettingsAsset;
use craft\web\assets\timepicker\TimepickerAsset;
use DateTime;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;
use LitEmoji\LitEmoji;
use yii\db\Schema;
use yii\validators\EmailValidator;

/**
 * Table represents a Table field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Table extends Field
{
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
    public static function valueType(): string
    {
        return 'array|null';
    }

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
     * @var string The type of database column the field should have in the content table
     * @phpstan-var 'auto'|Schema::TYPE_STRING|Schema::TYPE_TEXT|'mediumtext'
     */
    public string $columnType = Schema::TYPE_TEXT;

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        // Config normalization}
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

        // Convert default date cell values to ISO8601 strings
        if (!empty($config['columns']) && isset($config['defaults'])) {
            foreach ($config['columns'] as $colId => $col) {
                if (in_array($col['type'], ['date', 'time'], true)) {
                    foreach ($config['defaults'] as &$row) {
                        if (isset($row[$colId])) {
                            $row[$colId] = DateTimeHelper::toIso8601($row[$colId]) ?: null;
                        }
                    }
                }
            }
        }

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
        foreach ($this->columns as &$col) {
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
    public function getContentColumnType(): string
    {
        return $this->columnType;
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml(): ?string
    {
        $typeOptions = [
            'checkbox' => Craft::t('app', 'Checkbox'),
            'color' => Craft::t('app', 'Color'),
            'date' => Craft::t('app', 'Date'),
            'select' => Craft::t('app', 'Dropdown'),
            'email' => Craft::t('app', 'Email'),
            'lightswitch' => Craft::t('app', 'Lightswitch'),
            'multiline' => Craft::t('app', 'Multi-line text'),
            'number' => Craft::t('app', 'Number'),
            'singleline' => Craft::t('app', 'Single-line text'),
            'time' => Craft::t('app', 'Time'),
            'url' => Craft::t('app', 'URL'),
        ];

        // Make sure they are sorted alphabetically (post-translation)
        asort($typeOptions);

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
                'options' => $typeOptions,
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

        $view = Craft::$app->getView();

        $view->registerAssetBundle(TimepickerAsset::class);
        $view->registerAssetBundle(TableSettingsAsset::class);
        $view->registerJs('new Craft.TableFieldSettings(' .
            Json::encode($view->namespaceInputName('columns'), JSON_UNESCAPED_UNICODE) . ', ' .
            Json::encode($view->namespaceInputName('defaults'), JSON_UNESCAPED_UNICODE) . ', ' .
            Json::encode($this->columns, JSON_UNESCAPED_UNICODE) . ', ' .
            Json::encode($this->defaults ?? [], JSON_UNESCAPED_UNICODE) . ', ' .
            Json::encode($columnSettings, JSON_UNESCAPED_UNICODE) . ', ' .
            Json::encode($dropdownSettingsHtml, JSON_UNESCAPED_UNICODE) . ', ' .
            Json::encode($dropdownSettingsCols, JSON_UNESCAPED_UNICODE) .
            ');');

        $columnsField = $view->renderTemplate('_components/fieldtypes/Table/columntable.twig', [
            'cols' => $columnSettings,
            'rows' => $this->columns,
            'errors' => $this->getErrors('columns'),
        ]);

        $defaultsField = Cp::editableTableFieldHtml([
            'label' => Craft::t('app', 'Default Values'),
            'instructions' => Craft::t('app', 'Define the default values for the field.'),
            'id' => 'defaults',
            'name' => 'defaults',
            'allowAdd' => true,
            'allowReorder' => true,
            'allowDelete' => true,
            'cols' => $this->columns,
            'rows' => $this->defaults,
            'initJs' => false,
        ]);

        return $view->renderTemplate('_components/fieldtypes/Table/settings.twig', [
            'field' => $this,
            'columnsField' => $columnsField,
            'defaultsField' => $defaultsField,
        ]);
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
    protected function inputHtml(mixed $value, ?ElementInterface $element = null): string
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
    public function normalizeValue(mixed $value, ?ElementInterface $element = null): mixed
    {
        if (is_string($value) && !empty($value)) {
            $value = Json::decodeIfJson($value);
        } elseif ($value === null && $this->isFresh($element)) {
            $value = array_values($this->defaults ?? []);
        }

        if (!is_array($value) || empty($this->columns)) {
            return null;
        }

        // Normalize the values and make them accessible from both the col IDs and the handles
        foreach ($value as &$row) {
            foreach ($this->columns as $colId => $col) {
                if (array_key_exists($colId, $row)) {
                    $cellValue = $row[$colId];
                } elseif ($col['handle'] && array_key_exists($col['handle'], $row)) {
                    $cellValue = $row[$col['handle']];
                } else {
                    $cellValue = null;
                }
                $cellValue = $this->_normalizeCellValue($col['type'], $cellValue);
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
    public function serializeValue(mixed $value, ?ElementInterface $element = null): mixed
    {
        if (!is_array($value) || empty($this->columns)) {
            return null;
        }

        $serialized = [];

        foreach ($value as $row) {
            $serializedRow = [];
            foreach (array_keys($this->columns) as $colId) {
                $value = $row[$colId];

                if (is_string($value) && in_array($this->columns[$colId]['type'], ['singleline', 'multiline'], true)) {
                    $value = LitEmoji::unicodeToShortcode($value);
                }

                $serializedRow[$colId] = parent::serializeValue($value ?? null);
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
        $type = TableRowTypeGenerator::generateType($this);
        return Type::listOf($type);
    }

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    public function getContentGqlMutationArgumentType(): Type|array
    {
        $typeName = $this->handle . '_TableRowInput';

        if ($argumentType = GqlEntityRegistry::getEntity($typeName)) {
            return Type::listOf($argumentType);
        }

        $contentFields = TableRow::prepareRowFieldDefinition($this->columns, false);

        $argumentType = GqlEntityRegistry::createEntity($typeName, new InputObjectType([
            'name' => $typeName,
            'fields' => function() use ($contentFields) {
                return $contentFields;
            },
        ]));

        return Type::listOf($argumentType);
    }

    /**
     * Normalizes a cell’s value.
     *
     * @param string $type The cell type
     * @param mixed $value The cell value
     * @return mixed
     * @see normalizeValue()
     */
    private function _normalizeCellValue(string $type, mixed $value): mixed
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
                    $value = LitEmoji::shortcodeToUnicode($value);
                    return trim(preg_replace('/\R/u', "\n", $value));
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

        // Translate the column headings
        foreach ($this->columns as &$column) {
            if (!empty($column['heading'])) {
                $column['heading'] = Craft::t('site', $column['heading']);
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
                        'value' => $row[$colId],
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
            'allowAdd' => true,
            'allowDelete' => true,
            'allowReorder' => true,
            'addRowLabel' => Craft::t('site', $this->addRowLabel),
            'describedBy' => $this->describedBy,
        ]);
    }
}
