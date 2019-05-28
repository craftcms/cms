<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\fields\data\ColorData;
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;
use craft\validators\ColorValidator;
use craft\validators\UrlValidator;
use craft\web\assets\tablesettings\TableSettingsAsset;
use craft\web\assets\timepicker\TimepickerAsset;
use yii\db\Schema;
use yii\validators\EmailValidator;

/**
 * Table represents a Table field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Table extends Field
{
    // Static
    // =========================================================================

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

    // Properties
    // =========================================================================

    /**
     * @var string|null Custom add row button label
     */
    public $addRowLabel;

    /**
     * @var int|null Maximum number of Rows allowed
     */
    public $maxRows;

    /**
     * @var int|null Minimum number of Rows allowed
     */
    public $minRows;

    /**
     * @var array|null The columns that should be shown in the table
     */
    public $columns = [
        'col1' => [
            'heading' => '',
            'handle' => '',
            'type' => 'singleline'
        ]
    ];

    /**
     * @var array The default row values that new elements should have
     */
    public $defaults;

    /**
     * @var string The type of database column the field should have in the content table
     */
    public $columnType = Schema::TYPE_TEXT;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if ($this->addRowLabel === null) {
            $this->addRowLabel = Craft::t('app', 'Add a row');
        }

        if (!is_array($this->columns)) {
            $this->columns = [];
        } else {
            foreach ($this->columns as $colId => &$column) {
                if ($column['type'] === 'select') {
                    if (!isset($column['options'])) {
                        $column['options'] = [];
                    } else if (is_string($column['options'])) {
                        $column['options'] = Json::decode($column['options']);
                    }
                } else {
                    unset($column['options']);
                }
            }
            unset($column);
        }

        if (!is_array($this->defaults)) {
            $this->defaults = $this->id || $this->defaults === '' ? [] : [[]];
        } else {
            // Make sure the array is non-associative and with incrementing keys
            $this->defaults = array_values($this->defaults);
        }

        // Convert default date cell values to ISO8601 strings
        if (!empty($this->columns) && $this->defaults !== null) {
            foreach ($this->columns as $colId => $col) {
                if (in_array($col['type'], ['date', 'time'], true)) {
                    foreach ($this->defaults as &$row) {
                        if (isset($row[$colId])) {
                            $row[$colId] = DateTimeHelper::toIso8601($row[$colId]) ?: null;
                        }
                    }
                }
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::rules();
        $rules[] = [['minRows'], 'compare', 'compareAttribute' => 'maxRows', 'operator' => '<=', 'type' => 'number', 'when' => [$this, 'hasMaxRows']];
        $rules[] = [['maxRows'], 'compare', 'compareAttribute' => 'minRows', 'operator' => '>=', 'type' => 'number', 'when' => [$this, 'hasMinRows']];
        $rules[] = [['minRows', 'maxRows'], 'integer', 'min' => 0];
        $rules[] = [['columns'], 'validateColumns'];
        return $rules;
    }

    /**
     * Validatse the column configs.
     */
    public function validateColumns()
    {
        $hasErrors = false;
        foreach ($this->columns as &$col) {
            if ($col['handle'] && preg_match('/^col\d+$/', $col['handle'])) {
                $col['handle'] = [
                    'value' => $col['handle'],
                    'hasErrors' => true,
                ];
                $hasErrors = true;
            }
        }
        if ($hasErrors) {
            $this->addError('columns', Craft::t('app', 'Column handles can’t be in the format “{format}”.', [
                'format' => 'colX',
            ]));
        }
    }

    /**
     * @return bool whether minRows was set
     */
    public function hasMinRows(): bool
    {
        return $this->minRows;
    }

    /**
     * @return bool whether maxRows was set
     */
    public function hasMaxRows(): bool
    {
        return $this->maxRows;
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
    public function getSettingsHtml()
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
                'autopopulate' => 'handle'
            ],
            'handle' => [
                'heading' => Craft::t('app', 'Handle'),
                'code' => true,
                'type' => 'singleline'
            ],
            'width' => [
                'heading' => Craft::t('app', 'Width'),
                'code' => true,
                'type' => 'singleline',
                'width' => 50
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

        $dropdownSettingsHtml = Craft::$app->getView()->renderTemplateMacro('_includes/forms', 'editableTableField', [
            [
                'label' => Craft::t('app', 'Dropdown Options'),
                'instructions' => Craft::t('app', 'Define the available options.'),
                'id' => '__ID__',
                'name' => '__NAME__',
                'addRowLabel' => Craft::t('app', 'Add an option'),
                'cols' => $dropdownSettingsCols,
                'initJs' => false,
            ]
        ]);

        $view = Craft::$app->getView();

        $view->registerAssetBundle(TimepickerAsset::class);
        $view->registerAssetBundle(TableSettingsAsset::class);
        $view->registerJs('new Craft.TableFieldSettings(' .
            Json::encode($view->namespaceInputName('columns'), JSON_UNESCAPED_UNICODE) . ', ' .
            Json::encode($view->namespaceInputName('defaults'), JSON_UNESCAPED_UNICODE) . ', ' .
            Json::encode($this->columns, JSON_UNESCAPED_UNICODE) . ', ' .
            Json::encode($this->defaults, JSON_UNESCAPED_UNICODE) . ', ' .
            Json::encode($columnSettings, JSON_UNESCAPED_UNICODE) . ', ' .
            Json::encode($dropdownSettingsHtml, JSON_UNESCAPED_UNICODE) . ', ' .
            Json::encode($dropdownSettingsCols, JSON_UNESCAPED_UNICODE) .
            ');');

        $columnsField = $view->renderTemplate('_components/fieldtypes/Table/columntable', [
            'cols' => $columnSettings,
            'rows' => $this->columns,
        ]);

        $defaultsField = $view->renderTemplateMacro('_includes/forms', 'editableTableField', [
            [
                'label' => Craft::t('app', 'Default Values'),
                'instructions' => Craft::t('app', 'Define the default values for the field.'),
                'id' => 'defaults',
                'name' => 'defaults',
                'cols' => $this->columns,
                'rows' => $this->defaults,
                'initJs' => false,
                'errors' => $this->getErrors('columns'),
            ]
        ]);

        return $view->renderTemplate('_components/fieldtypes/Table/settings', [
            'field' => $this,
            'columnsField' => $columnsField,
            'defaultsField' => $defaultsField,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getInputHtml($value, ElementInterface $element = null): string
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
    public function validateTableData(ElementInterface $element)
    {
        /** @var Element $element */
        $value = $element->getFieldValue($this->handle);

        if (!empty($value) && !empty($this->columns)) {
            foreach ($value as $row) {
                foreach ($this->columns as $colId => $col) {
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
    public function normalizeValue($value, ElementInterface $element = null)
    {
        if (is_string($value) && !empty($value)) {
            $value = Json::decodeIfJson($value);
        } else if ($value === null && $this->isFresh($element)) {
            $value = array_values($this->defaults);
        }

        if (!is_array($value) || empty($this->columns)) {
            return null;
        }

        // Normalize the values and make them accessible from both the col IDs and the handles
        foreach ($value as &$row) {
            foreach ($this->columns as $colId => $col) {
                $row[$colId] = $this->_normalizeCellValue($col['type'], $row[$colId] ?? null);
                if ($col['handle'] && !isset($this->columns[$col['handle']])) {
                    $row[$col['handle']] = $row[$colId];
                }
            }
        }

        return $value;
    }

    /**
     * @inheritdoc
     */
    public function serializeValue($value, ElementInterface $element = null)
    {
        if (!is_array($value) || empty($this->columns)) {
            return null;
        }

        $serialized = [];

        foreach ($value as $row) {
            $serializedRow = [];
            foreach (array_keys($this->columns) as $colId) {
                $serializedRow[$colId] = parent::serializeValue($row[$colId] ?? null);
            }
            $serialized[] = $serializedRow;
        }

        return $serialized;
    }

    /**
     * @inheritdoc
     */
    public function getStaticHtml($value, ElementInterface $element): string
    {
        return $this->_getInputHtml($value, $element, true);
    }

    // Private Methods
    // =========================================================================

    /**
     * Normalizes a cell’s value.
     *
     * @param string $type The cell type
     * @param mixed $value The cell value
     * @return mixed
     * @see normalizeValue()
     */
    private function _normalizeCellValue(string $type, $value)
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
     * @param string|null &$error The error text to set on the element
     * @return bool Whether the value is valid
     * @see normalizeValue()
     */
    private function _validateCellValue(string $type, $value, string &$error = null): bool
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
    private function _getInputHtml($value, ElementInterface $element = null, bool $static): string
    {
        /** @var Element $element */
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

        $view = Craft::$app->getView();
        $id = $view->formatInputId($this->handle);

        return $view->renderTemplate('_includes/forms/editableTable', [
            'id' => $id,
            'name' => $this->handle,
            'cols' => $this->columns,
            'rows' => $value,
            'minRows' => $this->minRows,
            'maxRows' => $this->maxRows,
            'static' => $static,
            'addRowLabel' => Craft::t('site', $this->addRowLabel),
        ]);
    }
}
