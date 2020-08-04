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
use craft\base\PreviewableFieldInterface;
use craft\elements\db\ElementQueryInterface;
use craft\fields\data\MultiOptionsFieldData;
use craft\fields\data\OptionData;
use craft\fields\data\SingleOptionFieldData;
use craft\gql\arguments\OptionField as OptionFieldArguments;
use craft\gql\GqlEntityRegistry;
use craft\gql\resolvers\OptionField as OptionFieldResolver;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\Json;
use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\Type;
use yii\db\Schema;

/**
 * BaseOptionsField is the base class for classes representing an options field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
abstract class BaseOptionsField extends Field implements PreviewableFieldInterface
{
    /**
     * @var array|null The available options
     */
    public $options;

    /**
     * @var bool Whether the field should support multiple selections
     */
    protected $multi = false;

    /**
     * @var bool Whether the field should support optgroups
     */
    protected $optgroups = false;

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
                } else if (!empty($option['isOptgroup'])) {
                    // isOptgroup will be set if this is a settings request
                    $options[] = [
                        'optgroup' => $option['label'],
                    ];
                } else {
                    unset($option['isOptgroup']);
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
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = ['options', 'validateOptions'];
        return $rules;
    }

    /**
     * Validates the field options.
     *
     * @since 3.3.5
     */
    public function validateOptions()
    {
        $labels = [];
        $values = [];
        $hasDuplicateLabels = false;
        $hasDuplicateValues = false;
        $optgroup = '__root__';

        foreach ($this->options as &$option) {
            // Ignore optgroups
            if (array_key_exists('optgroup', $option)) {
                $optgroup = $option['optgroup'];
                continue;
            }

            $label = (string)$option['label'];
            $value = (string)$option['value'];
            if (isset($labels[$optgroup][$label])) {
                $option['label'] = [
                    'value' => $label,
                    'hasErrors' => true,
                ];
                $hasDuplicateLabels = true;
            }
            if (isset($values[$value])) {
                $option['value'] = [
                    'value' => $value,
                    'hasErrors' => true,
                ];
                $hasDuplicateValues = true;
            }
            $labels[$optgroup][$label] = $values[$value] = true;
        }

        if ($hasDuplicateLabels) {
            $this->addError('options', Craft::t('app', 'All option labels must be unique.'));
        }
        if ($hasDuplicateValues) {
            $this->addError('options', Craft::t('app', 'All option values must be unique.'));
        }
    }

    /**
     * @inheritdoc
     */
    public function getContentColumnType(): string
    {
        if ($this->multi) {
            // See how much data we could possibly be saving if everything was selected.
            $length = 0;

            foreach ($this->options() as $option) {
                if (!empty($option['value'])) {
                    // +3 because it will be json encoded. Includes the surrounding quotes and comma.
                    $length += strlen($option['value']) + 3;
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

        $cols = [];
        if ($this->optgroups) {
            $cols['isOptgroup'] = [
                'heading' => Craft::t('app', 'Optgroup?'),
                'type' => 'checkbox',
                'class' => 'thin',
                'toggle' => ['!value', '!default'],
            ];
        }
        $cols['label'] = [
            'heading' => Craft::t('app', 'Option Label'),
            'type' => 'singleline',
            'autopopulate' => 'value'
        ];
        $cols['value'] = [
            'heading' => Craft::t('app', 'Value'),
            'type' => 'singleline',
            'class' => 'code'
        ];
        $cols['default'] = [
            'heading' => Craft::t('app', 'Default?'),
            'type' => 'checkbox',
            'radioMode' => !$this->multi,
            'class' => 'thin'
        ];

        $rows = [];
        foreach ($this->options as $option) {
            if (isset($option['optgroup'])) {
                $option['isOptgroup'] = true;
                $option['label'] = ArrayHelper::remove($option, 'optgroup');
            }
            $rows[] = $option;
        }

        return Craft::$app->getView()->renderTemplateMacro('_includes/forms', 'editableTableField',
            [
                [
                    'label' => $this->optionsSettingLabel(),
                    'instructions' => Craft::t('app', 'Define the available options.'),
                    'id' => 'options',
                    'name' => 'options',
                    'addRowLabel' => Craft::t('app', 'Add an option'),
                    'cols' => $cols,
                    'rows' => $rows,
                    'errors' => $this->getErrors('options'),
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

        if (is_string($value) && (
                $value === '' ||
                strpos($value, '[') === 0 ||
                strpos($value, '{') === 0
            )) {
            $value = Json::decodeIfJson($value);
        } else if ($value === null && $this->isFresh($element)) {
            $value = $this->defaultValue();
        }

        // Normalize to an array of strings
        $selectedValues = [];
        foreach ((array)$value as $val) {
            $selectedValues[] = (string)$val;
        }

        $rawOptions = $this->options();

        if ($this->multi) {
            // Convert the value to a MultiOptionsFieldData object
            $options = [];
            if (!empty($selectedValues)) {
                foreach ($rawOptions as $option) {
                    if (!isset($option['optgroup']) && in_array((string)$option['value'], $selectedValues, true)) {
                        $options[] = new OptionData($option['label'], (string)$option['value'], true);
                    }
                }
            }
            $value = new MultiOptionsFieldData($options);
        } else {
            // Convert the value to a SingleOptionFieldData object
            $value = $label = null;
            if (!empty($selectedValues)) {
                foreach ($rawOptions as $option) {
                    if (!isset($option['optgroup']) && in_array((string)$option['value'], $selectedValues, true)) {
                        $value = (string)$option['value'];
                        $label = $option['label'];
                        break;
                    }
                }
            }
            $value = new SingleOptionFieldData($label, $value, true);
        }

        $options = [];

        foreach ($rawOptions as $option) {
            if (isset($option['optgroup'])) {
                continue;
            }
            $selected = in_array($option['value'], $selectedValues, true);
            $options[] = new OptionData($option['label'], $option['value'], $selected);
        }

        $value->setOptions($options);

        return $value;
    }

    /**
     * @inheritdoc
     */
    public function serializeValue($value, ElementInterface $element = null)
    {
        if ($value instanceof MultiOptionsFieldData) {
            $serialized = [];
            foreach ($value as $selectedValue) {
                /** @var OptionData $selectedValue */
                $serialized[] = $selectedValue->value;
            }
            return $serialized;
        }

        return parent::serializeValue($value, $element);
    }

    /**
     * @inheritdoc
     * @since 3.4.6
     */
    public function modifyElementsQuery(ElementQueryInterface $query, $value)
    {
        // foo => *"foo"*
        if ($this->multi) {
            if (is_string($value)) {
                if (preg_match('/^(not\s+)?([^\*\[\]"]+)$/', $value, $match)) {
                    $value = "{$match[1]}*\"{$match[2]}\"*";
                }
            } else if (is_array($value)) {
                foreach ($value as &$v) {
                    if (!in_array(strtolower($v), ['and', 'or', 'not']) && preg_match('/^(not\s+)?([^\*\[\]"]+)$/', $v, $match)) {
                        $v = "{$match[1]}*\"{$match[2]}\"*";
                    }
                }
            }
        }

        return parent::modifyElementsQuery($query, $value);
    }

    /**
     * @inheritdoc
     */
    public function getElementValidationRules(): array
    {
        // Get all of the acceptable values
        $range = [];

        foreach ($this->options() as $option) {
            if (!isset($option['optgroup'])) {
                // Cast the option value to a string in case it is an integer
                $range[] = (string)$option['value'];
            }
        }

        return [
            ['in', 'range' => $range, 'allowArray' => $this->multi],
        ];
    }

    /**
     * @inheritdoc
     */
    public function isValueEmpty($value, ElementInterface $element): bool
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
        return (string)$value->label;
    }

    /**
     * Returns whether the field type supports storing multiple selected options.
     *
     * @return bool
     * @see multi
     */
    public function getIsMultiOptionsField(): bool
    {
        return $this->multi;
    }

    /**
     * @inheritdoc
     * @since 3.3.0
     */
    public function getContentGqlType()
    {
        return [
            'name' => $this->handle,
            'type' => $this->multi ? Type::listOf(Type::string()) : Type::string(),
            'args' => OptionFieldArguments::getArguments(),
            'resolve' => OptionFieldResolver::class . '::resolve'
        ];
    }

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    public function getContentGqlMutationArgumentType()
    {
        $typeName = $this->handle . '_FieldEnum';

        if ($enumType = GqlEntityRegistry::getEntity($typeName)) {
            return $enumType;
        }

        $values = [];

        foreach ($this->options as $option) {
            if (!isset($option['optgroup'])) {
                $values[] = $option['value'];
            }
        }

        $enumType = GqlEntityRegistry::createEntity($typeName, new EnumType([
            'name' => $typeName,
            'values' => $values,
        ]));


        $type = $this->multi ? Type::listOf($enumType) : $enumType;

        return [
            'name' => $this->handle,
            'type' => $type,
            'description' => $this->instructions,
        ];
    }

    /**
     * Returns the label for the Options setting.
     *
     * @return string
     */
    abstract protected function optionsSettingLabel(): string;

    /**
     * Returns the available options (and optgroups) for the field.
     *
     * Each option should be defined as a nested array with the following keys:
     *
     * - `label` – The option label
     * - `value`– The option value
     *
     * To define an optgroup, add an array with an `optgroup` key, set to the label of the optgroup.
     *
     * ```php
     * [
     *   ['label' => 'Foo', 'value' => 'foo'],
     *   ['label' => 'Bar', 'value' => 'bar'],
     *   ['optgroup' => 'Fruit']
     *   ['label' => 'Apple', 'value' => 'apple'],
     *   ['label' => 'Orange', 'value' => 'orange'],
     *   ['label' => 'Banana', 'value' => 'banana'],
     * ]
     * ```
     *
     * @return array
     */
    protected function options(): array
    {
        return $this->options ?? [];
    }

    /**
     * Returns the field options, with labels run through Craft::t().
     *
     * @return array
     */
    protected function translatedOptions(): array
    {
        $translatedOptions = [];

        foreach ($this->options() as $option) {
            if (isset($option['optgroup'])) {
                $translatedOptions[] = [
                    'optgroup' => Craft::t('site', $option['optgroup']),
                ];
            } else {
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
     * @return string|null
     * @deprecated in 3.4.24
     */
    protected function optionLabel(string $value = null)
    {
        foreach ($this->options() as $option) {
            if (!isset($option['optgroup']) && (string)$option['value'] === $value) {
                return $option['label'];
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

            foreach ($this->options() as $option) {
                if (!empty($option['default'])) {
                    $defaultValues[] = $option['value'];
                }
            }

            return $defaultValues;
        }

        foreach ($this->options() as $option) {
            if (!empty($option['default'])) {
                return $option['value'];
            }
        }

        return null;
    }
}
