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
use craft\base\PreviewableFieldInterface;
use craft\fields\data\MultiOptionsFieldData;
use craft\fields\data\OptionData;
use craft\fields\data\SingleOptionFieldData;
use craft\helpers\Db;
use craft\helpers\Json;
use yii\db\Schema;

/**
 * BaseOptionsField is the base class for classes representing an options field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
abstract class BaseOptionsField extends Field implements PreviewableFieldInterface
{
    // Properties
    // =========================================================================

    /**
     * @var array|null The available options
     */
    public $options;

    /**
     * @var bool Whether the field should support multiple selections
     */
    protected $multi = false;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        // Normalize the options
        $options = [];

        if (is_array($this->options)) {
            foreach ($this->options as $key => $option) {
                // Old school?
                if (!is_array($option)) {
                    $options[] = [
                        'label' => $option,
                        'value' => $key,
                        'default' => ''
                    ];
                } else {
                    $options[] = $option;
                }
            }
        }

        $this->options = $options;
    }

    /**
     * @inheritdoc
     */
    public function settingsAttributes(): array
    {
        $attributes = parent::settingsAttributes();
        $attributes[] = 'options';

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    public function getContentColumnType(): string
    {
        if ($this->multi) {
            // See how much data we could possibly be saving if everything was selected.
            $length = 0;

            if ($this->options) {
                foreach ($this->options as $option) {
                    if (!empty($option['value'])) {
                        // +3 because it will be json encoded. Includes the surrounding quotes and comma.
                        $length += strlen($option['value']) + 3;
                    }
                }
            }

            // Add +2 for the outer brackets and -1 for the last comma.
            return Db::getTextualColumnTypeByContentLength($length + 1);
        }

        return Schema::TYPE_STRING;
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml()
    {
        if (empty($this->options)) {
            // Give it a default row
            $this->options = [['label' => '', 'value' => '']];
        }

        return Craft::$app->getView()->renderTemplateMacro('_includes/forms', 'editableTableField',
            [
                [
                    'label' => $this->optionsSettingLabel(),
                    'instructions' => Craft::t('app', 'Define the available options.'),
                    'id' => 'options',
                    'name' => 'options',
                    'addRowLabel' => Craft::t('app', 'Add an option'),
                    'cols' => [
                        'label' => [
                            'heading' => Craft::t('app', 'Option Label'),
                            'type' => 'singleline',
                            'autopopulate' => 'value'
                        ],
                        'value' => [
                            'heading' => Craft::t('app', 'Value'),
                            'type' => 'singleline',
                            'class' => 'code'
                        ],
                        'default' => [
                            'heading' => Craft::t('app', 'Default?'),
                            'type' => 'checkbox',
                            'radioMode' => !$this->multi,
                            'class' => 'thin'
                        ],
                    ],
                    'rows' => $this->options
                ]
            ]);
    }

    /**
     * @inheritdoc
     */
    public function normalizeValue($value, ElementInterface $element = null)
    {
        if ($value instanceof MultiOptionsFieldData || $value instanceof SingleOptionFieldData) {
            return $value;
        }

        if (is_string($value)) {
            $value = Json::decodeIfJson($value);
        }

        if ($this->multi) {
            // In case the field used to be a single-option field
            $value = (array)$value;

            // Convert all the values to OptionData objects
            foreach ($value as &$val) {
                $label = $this->optionLabel($val);
                $val = new OptionData($label, $val, true);
            }
            unset($val);

            $value = new MultiOptionsFieldData($value);
        } else {
            // In case the field used to be a multi-option field
            if (is_array($value)) {
                $value = reset($value) ?: null;
            }

            // Convert the value to a SingleOptionFieldData object
            $label = $this->optionLabel($value);
            $value = new SingleOptionFieldData($label, $value, true);
        }

        $options = [];
        $selectedValues = (array)$value;

        if ($this->options) {
            foreach ($this->options as $option) {
                $selected = in_array($option['value'], $selectedValues, true);
                $options[] = new OptionData($option['label'], $option['value'], $selected);
            }
        }

        $value->setOptions($options);

        return $value;
    }

    /**
     * @inheritdoc
     */
    public function getElementValidationRules(): array
    {
        // Get all of the acceptable values
        $range = [];

        if ($this->options) {
            foreach ($this->options as $option) {
                $range[] = $option['value'];
            }
        }

        return [
            ['in', 'range' => $range, 'allowArray' => $this->multi],
        ];
    }

    /**
     * @inheritdoc
     */
    public function isEmpty($value): bool
    {
        /** @var MultiOptionsFieldData|SingleOptionFieldData $value */
        if ($value instanceof SingleOptionFieldData) {
            return $value->value === null || $value->value === '';
        }

        return count($value) === 0;
    }

    /**
     * @inheritdoc
     */
    public function getTableAttributeHtml($value, ElementInterface $element): string
    {
        if ($this->multi) {
            /** @var MultiOptionsFieldData $value */
            $labels = [];

            foreach ($value as $option) {
                $labels[] = $option->label;
            }

            return implode(', ', $labels);
        }

        /** @var SingleOptionFieldData $value */
        return (string)$value->value;
    }

    // Protected Methods
    // =========================================================================

    /**
     * Returns the label for the Options setting.
     *
     * @return string
     */
    abstract protected function optionsSettingLabel(): string;

    /**
     * Returns the field options, with labels run through Craft::t().
     *
     * @return array
     */
    protected function translatedOptions(): array
    {
        $translatedOptions = [];

        if ($this->options) {
            foreach ($this->options as $option) {
                $translatedOptions[] = [
                    'label' => Craft::t('site', $option['label']),
                    'value' => $option['value']
                ];
            }
        }

        return $translatedOptions;
    }

    /**
     * Returns an option's label by its value.
     *
     * @param string|null $value
     *
     * @return string|null
     */
    protected function optionLabel(string $value = null)
    {
        if ($this->options) {
            foreach ($this->options as $option) {
                if ($option['value'] == $value) {
                    return $option['label'];
                }
            }
        }

        return $value;
    }

    /**
     * Returns the default field value.
     *
     * @return string[]|string|null
     */
    protected function defaultValue()
    {
        if ($this->multi) {
            $defaultValues = [];

            if ($this->options) {
                foreach ($this->options as $option) {
                    if (!empty($option['default'])) {
                        $defaultValues[] = $option['value'];
                    }
                }
            }

            return $defaultValues;
        }

        if ($this->options) {
            foreach ($this->options as $option) {
                if (!empty($option['default'])) {
                    return $option['value'];
                }
            }
        }

        return null;
    }
}
