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
use craft\events\DefineInputOptionsEvent;
use craft\fields\conditions\OptionsFieldConditionRule;
use craft\fields\data\MultiOptionsFieldData;
use craft\fields\data\OptionData;
use craft\fields\data\SingleOptionFieldData;
use craft\gql\arguments\OptionField as OptionFieldArguments;
use craft\gql\resolvers\OptionField as OptionFieldResolver;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\StringHelper;
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
     * @event DefineInputOptionsEvent Event triggered when defining the options for the field's input.
     * @since 4.4.0
     */
    public const EVENT_DEFINE_OPTIONS = 'defineOptions';

    /**
     * @var array The available options
     */
    public array $options;

    /**
     * @var bool Whether the field should support multiple selections
     */
    protected bool $multi = false;

    /**
     * @var bool Whether the field should support optgroups
     */
    protected bool $optgroups = false;

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        // Not possible to override multi or optgroups
        unset($config['multi'], $config['optgroups']);

        // Normalize the options
        $options = [];
        if (isset($config['options']) && is_array($config['options'])) {
            foreach ($config['options'] as $key => $option) {
                // Old school?
                if (!is_array($option)) {
                    $options[] = [
                        'label' => $option,
                        'value' => $key,
                        'default' => '',
                    ];
                } elseif (!empty($option['isOptgroup'])) {
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
        $config['options'] = $options;

        parent::__construct($config);
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
    public function validateOptions(): void
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
    public function getSettingsHtml(): ?string
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
            'autopopulate' => 'value',
        ];
        $cols['value'] = [
            'heading' => Craft::t('app', 'Value'),
            'type' => 'singleline',
            'class' => 'code',
        ];
        $cols['default'] = [
            'heading' => Craft::t('app', 'Default?'),
            'type' => 'checkbox',
            'radioMode' => !$this->multi,
            'class' => 'thin',
        ];

        $rows = [];
        foreach ($this->options as $option) {
            if (isset($option['optgroup'])) {
                $option['isOptgroup'] = true;
                $option['label'] = ArrayHelper::remove($option, 'optgroup');
            }
            $rows[] = $option;
        }

        return Cp::editableTableFieldHtml([
            'label' => $this->optionsSettingLabel(),
            'instructions' => Craft::t('app', 'Define the available options.'),
            'id' => 'options',
            'name' => 'options',
            'addRowLabel' => Craft::t('app', 'Add an option'),
            'allowAdd' => true,
            'allowReorder' => true,
            'allowDelete' => true,
            'cols' => $cols,
            'rows' => $rows,
            'errors' => $this->getErrors('options'),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function normalizeValue(mixed $value, ?ElementInterface $element = null): mixed
    {
        if ($value instanceof MultiOptionsFieldData || $value instanceof SingleOptionFieldData) {
            return $value;
        }

        if (is_string($value) && (
                str_starts_with($value, '[') ||
                str_starts_with($value, '{')
            )) {
            $value = Json::decodeIfJson($value);
        } elseif ($value === '' && $this->multi) {
            $value = [];
        } elseif ($value === null && $this->isFresh($element)) {
            $value = $this->defaultValue();
        }

        // Normalize to an array of strings
        $selectedValues = [];
        foreach ((array)$value as $val) {
            $val = (string)$val;
            if (str_starts_with($val, 'base64:')) {
                $val = base64_decode(StringHelper::removeLeft($val, 'base64:'));
            }
            $selectedValues[] = $val;
        }

        $options = [];
        $optionValues = [];
        $optionLabels = [];
        foreach ($this->options() as $option) {
            if (!isset($option['optgroup'])) {
                $selected = in_array($option['value'], $selectedValues, true);
                $options[] = new OptionData($option['label'], $option['value'], $selected, true);
                $optionValues[] = (string)$option['value'];
                $optionLabels[] = (string)$option['label'];
            }
        }

        if ($this->multi) {
            // Convert the value to a MultiOptionsFieldData object
            $selectedOptions = [];
            foreach ($selectedValues as $selectedValue) {
                $index = array_search($selectedValue, $optionValues, true);
                $valid = $index !== false;
                $label = $valid ? $optionLabels[$index] : null;
                $selectedOptions[] = new OptionData($label, $selectedValue, true, $valid);
            }
            $value = new MultiOptionsFieldData($selectedOptions);
        } elseif (!empty($selectedValues)) {
            // Convert the value to a SingleOptionFieldData object
            $selectedValue = reset($selectedValues);
            $index = array_search($selectedValue, $optionValues, true);
            $valid = $index !== false;
            $label = $valid ? $optionLabels[$index] : null;
            $value = new SingleOptionFieldData($label, $selectedValue, true, $valid);
        } else {
            $value = new SingleOptionFieldData(null, null, true, false);
        }

        $value->setOptions($options);

        return $value;
    }

    /**
     * @inheritdoc
     */
    public function serializeValue(mixed $value, ?ElementInterface $element = null): mixed
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
     */
    protected function searchKeywords(mixed $value, ElementInterface $element): string
    {
        $keywords = [];

        if ($this->multi) {
            /** @var MultiOptionsFieldData|OptionData[] $value */
            foreach ($value as $option) {
                $keywords[] = $option->value;
                $keywords[] = $option->label;
            }
        } else {
            /** @var SingleOptionFieldData $value */
            if ($value->value !== null) {
                $keywords[] = $value->value;
                $keywords[] = $value->label;
            }
        }

        return implode(' ', $keywords);
    }

    /**
     * @inheritdoc
     */
    public function getElementConditionRuleType(): array|string|null
    {
        return OptionsFieldConditionRule::class;
    }

    /**
     * @inheritdoc
     * @since 3.4.6
     */
    public function modifyElementsQuery(ElementQueryInterface $query, mixed $value): void
    {
        // foo => *"foo"*
        if ($this->multi) {
            if (is_string($value)) {
                if (preg_match('/^(not\s+)?([^\*\[\]"]+)$/', $value, $match)) {
                    $value = "$match[1]*\"$match[2]\"*";
                }
            } elseif (is_array($value)) {
                foreach ($value as &$v) {
                    if (!in_array(strtolower($v), ['and', 'or', 'not']) && preg_match('/^(not\s+)?([^\*\[\]"]+)$/', $v, $match)) {
                        $v = "$match[1]*\"$match[2]\"*";
                    }
                }
            }
        }

        parent::modifyElementsQuery($query, $value);
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
            [
                'in',
                'range' => $range,
                'allowArray' => $this->multi,
                // Don't allow saving invalid blank values via Selectize
                'skipOnEmpty' => !($this instanceof Dropdown && Craft::$app->getRequest()->getIsCpRequest()),
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function isValueEmpty(mixed $value, ElementInterface $element): bool
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
    public function getTableAttributeHtml(mixed $value, ElementInterface $element): string
    {
        if ($this->multi) {
            /** @var MultiOptionsFieldData $value */
            $labels = [];

            foreach ($value as $option) {
                /** @var OptionData $option */
                if ($option->value) {
                    $labels[] = Craft::t('site', $option->label);
                }
            }

            return implode(', ', $labels);
        }

        /** @var SingleOptionFieldData $value */
        return $value->value ? Craft::t('site', (string)$value->label) : '';
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
    public function getContentGqlType(): Type|array
    {
        return [
            'name' => $this->handle,
            'type' => $this->multi ? Type::listOf(Type::string()) : Type::string(),
            'args' => OptionFieldArguments::getArguments(),
            'resolve' => OptionFieldResolver::class . '::resolve',
        ];
    }

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    public function getContentGqlMutationArgumentType(): Type|array
    {
        $values = [];

        foreach ($this->options as $option) {
            if (!isset($option['optgroup'])) {
                $values[] = '“' . $option['value'] . '”';
            }
        }

        return [
            'name' => $this->handle,
            'type' => $this->multi ? Type::listOf(Type::string()) : Type::string(),
            'description' => Craft::t('app', 'The allowed values are [{values}]', ['values' => implode(', ', $values)]),
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
     * @param bool $encode Whether the option values should be base64-encoded
     * @param mixed $value The field’s value. This will either be the [[normalizeValue()|normalized value]],
     * raw POST data (i.e. if there was a validation error), or null
     * @param ElementInterface|null $element The element the field is associated with, if there is one
     * @return array
     */
    protected function translatedOptions(bool $encode = false, mixed $value = null, ?ElementInterface $element = null): array
    {
        $options = $this->options();
        $translatedOptions = [];

        if ($this->hasEventHandlers(self::EVENT_DEFINE_OPTIONS)) {
            $event = new DefineInputOptionsEvent([
                'options' => $options,
                'value' => $value,
                'element' => $element,
            ]);
            $this->trigger(self::EVENT_DEFINE_OPTIONS, $event);
            $options = $event->options;
        }

        foreach ($options as $option) {
            if (isset($option['optgroup'])) {
                $translatedOptions[] = [
                    'optgroup' => Craft::t('site', $option['optgroup']),
                ];
            } else {
                $translatedOptions[] = [
                    'label' => Craft::t('site', $option['label']),
                    'value' => $encode ? $this->encodeValue($option['value']) : $option['value'],
                ];
            }
        }

        return $translatedOptions;
    }

    /**
     * Base64-encodes a value.
     *
     * @param OptionData|MultiOptionsFieldData|string|null $value
     * @return string|array|null
     * @since 4.0.6
     */
    protected function encodeValue(OptionData|MultiOptionsFieldData|string|null $value): string|array|null
    {
        if ($value instanceof MultiOptionsFieldData) {
            /** @var OptionData[] $options */
            $options = (array)$value;
            return array_map(fn(OptionData $value) => $this->encodeValue($value), $options);
        }

        if ($value instanceof OptionData) {
            $value = $value->value;
        }

        if ($value === null || $value === '') {
            return $value;
        }

        return sprintf('base64:%s', base64_encode($value));
    }

    /**
     * Returns the default field value.
     *
     * @return string[]|string|null
     */
    protected function defaultValue(): array|string|null
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
