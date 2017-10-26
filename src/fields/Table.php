<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\fields;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\helpers\Json;
use craft\web\assets\tablesettings\TableSettingsAsset;
use yii\db\Schema;

/**
 * Table represents a Table field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
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

    // Properties
    // =========================================================================

    /**
     * @var array|null The columns that should be shown in the table
     */
    public $columns;

    /**
     * @var array The default row values that new elements should have
     */
    public $defaults = [];

    /**
     * @var string The type of database column the field should have in the content table
     */
    public $columnType = Schema::TYPE_TEXT;

    // Public Methods
    // =========================================================================

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
        $columns = $this->columns;
        $defaults = $this->defaults;

        if (empty($columns)) {
            $columns = [
                'col1' => [
                    'heading' => '',
                    'handle' => '',
                    'type' => 'singleline'
                ]
            ];

            // Update the actual settings model for getInputHtml()
            $this->columns = $columns;
        }

        if ($defaults === null) {
            $defaults = ['row1' => []];
        }

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
                'options' => [
                    'singleline' => Craft::t('app', 'Single-line Text'),
                    'multiline' => Craft::t('app', 'Multi-line text'),
                    'number' => Craft::t('app', 'Number'),
                    'checkbox' => Craft::t('app', 'Checkbox'),
                    'lightswitch' => Craft::t('app', 'Lightswitch'),
                ]
            ],
        ];

        $view = Craft::$app->getView();

        $view->registerAssetBundle(TableSettingsAsset::class);
        $view->registerJs('new Craft.TableFieldSettings('.
            Json::encode(Craft::$app->getView()->namespaceInputName('columns'), JSON_UNESCAPED_UNICODE).', '.
            Json::encode(Craft::$app->getView()->namespaceInputName('defaults'), JSON_UNESCAPED_UNICODE).', '.
            Json::encode($columns, JSON_UNESCAPED_UNICODE).', '.
            Json::encode($defaults, JSON_UNESCAPED_UNICODE).', '.
            Json::encode($columnSettings, JSON_UNESCAPED_UNICODE).
            ');');

        $columnsField = $view->renderTemplateMacro('_includes/forms', 'editableTableField',
            [
                [
                    'label' => Craft::t('app', 'Table Columns'),
                    'instructions' => Craft::t('app', 'Define the columns your table should have.'),
                    'id' => 'columns',
                    'name' => 'columns',
                    'cols' => $columnSettings,
                    'rows' => $columns,
                    'addRowLabel' => Craft::t('app', 'Add a column'),
                    'initJs' => false
                ]
            ]);

        $defaultsField = $view->renderTemplateMacro('_includes/forms', 'editableTableField',
            [
                [
                    'label' => Craft::t('app', 'Default Values'),
                    'instructions' => Craft::t('app', 'Define the default values for the field.'),
                    'id' => 'defaults',
                    'name' => 'defaults',
                    'cols' => $columns,
                    'rows' => $defaults,
                    'initJs' => false
                ]
            ]);

        return Craft::$app->getView()->renderTemplate('_components/fieldtypes/Table/settings', [
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
        $input = '<input type="hidden" name="'.$this->handle.'" value="">';

        $tableHtml = $this->_getInputHtml($value, $element, false);

        if ($tableHtml) {
            $input .= $tableHtml;
        }

        return $input;
    }

    /**
     * @inheritdoc
     */
    public function normalizeValue($value, ElementInterface $element = null)
    {
        if (is_string($value) && !empty($value)) {
            $value = Json::decode($value);
        }

        if (is_array($value) && !empty($this->columns)) {
            // Make the values accessible from both the col IDs and the handles
            foreach ($value as &$row) {
                foreach ($this->columns as $colId => $col) {
                    if ($col['handle']) {
                        $row[$col['handle']] = ($row[$colId] ?? null);
                    }
                }
            }

            return $value;
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function serializeValue($value, ElementInterface $element = null)
    {
        if (is_array($value)) {
            // Drop the string row keys
            $value = array_values($value);

            // Drop the column handle values
            if (!empty($this->columns)) {
                foreach ($value as &$row) {
                    foreach ($this->columns as $colId => $col) {
                        if ($col['handle']) {
                            unset($row[$col['handle']]);
                        }
                    }
                }
            }
        }

        return $value;
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
     * Returns the field's input HTML.
     *
     * @param mixed                 $value
     * @param ElementInterface|null $element
     * @param bool                  $static
     *
     * @return string|null
     */
    private function _getInputHtml($value, ElementInterface $element = null, bool $static)
    {
        $columns = $this->columns;

        if (!empty($columns)) {
            // Translate the column headings
            foreach ($columns as &$column) {
                if (!empty($column['heading'])) {
                    $column['heading'] = Craft::t('site', $column['heading']);
                }
            }
            unset($column);

            if ($this->isFresh($element)) {
                $defaults = $this->defaults;

                if (is_array($defaults)) {
                    $value = array_values($defaults);
                }
            }

            $id = Craft::$app->getView()->formatInputId($this->handle);

            return Craft::$app->getView()->renderTemplate('_includes/forms/editableTable',
                [
                    'id' => $id,
                    'name' => $this->handle,
                    'cols' => $columns,
                    'rows' => $value,
                    'static' => $static
                ]);
        }

        return null;
    }
}
