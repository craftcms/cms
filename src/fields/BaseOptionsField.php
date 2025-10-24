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
use craft\base\MergeableFieldInterface;
use craft\base\PreviewableFieldInterface;
use craft\db\QueryParam;
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
use craft\helpers\Html;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\validators\ColorValidator;
use GraphQL\Type\Definition\Type;
use yii\db\Schema;

/**
 * BaseOptionsField is the base class for classes representing an options field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
abstract class BaseOptionsField extends Field implements PreviewableFieldInterface, MergeableFieldInterface, CrossSiteCopyableFieldInterface
{
    /**
     * @event DefineInputOptionsEvent Event triggered when defining the options for the field's input.
     * @since 4.4.0
     */
    public const EVENT_DEFINE_OPTIONS = 'defineOptions';

    /**
     * @var bool Whether the field should support multiple selections
     */
    protected static bool $multi = false;

    /**
     * @var bool Whether the field should support optgroups
     */
    protected static bool $optgroups = false;

    /**
     * @var bool Whether field options should include an icon setting
     * @since 5.7.0
     */
    protected static bool $optionIcons = false;

    /**
     * @var bool Whether field options should include a color setting
     * @since 5.7.0
     */
    protected static bool $optionColors = false;

    /**
     * @var bool Whether the field should allow adding a custom option
     * @since 5.5.0
     */
    protected static bool $allowCustomOptions = false;

    /**
     * @inheritdoc
     */
    public static function phpType(): string
    {
        return sprintf('\\%s', static::$multi ? MultiOptionsFieldData::class : SingleOptionFieldData::class);
    }

    /**
     * @inheritdoc
     */
    public static function dbType(): string
    {
        return static::$multi ? Schema::TYPE_JSON : Schema::TYPE_STRING;
    }

    /**
     * @inheritdoc
     */
    public static function queryCondition(array $instances, mixed $value, array &$params): ?array
    {
        if (static::$multi) {
            $param = QueryParam::parse($value);

            if (empty($param->values)) {
                return null;
            }

            if ($param->operator === QueryParam::NOT) {
                $param->operator = QueryParam::OR;
                $negate = true;
            } else {
                $negate = false;
            }

            $condition = [$param->operator];
            $qb = Craft::$app->getDb()->getQueryBuilder();
            $valueSql = static::valueSql($instances);

            foreach ($param->values as $value) {
                if (
                    is_string($value) &&
                    in_array(strtolower($value), [':empty:', ':notempty:', 'not :empty:'])
                ) {
                    $condition[] = Db::parseParam($valueSql, $value, columnType: Schema::TYPE_JSON);
                } else {
                    $condition[] = $qb->jsonContains($valueSql, $value);
                }
            }

            return $negate ? ['not', $condition] : $condition;
        }

        return parent::queryCondition($instances, $value, $params);
    }

    /**
     * @var array The available options
     */
    public array $options;

    /**
     * @var bool Whether a custom option is allowed.
     * @since 5.5.0
     */
    public bool $customOptions = false;

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
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

        // remove unused settings
        unset($config['multi'], $config['optgroups'], $config['columnType']);

        if (!static::$allowCustomOptions) {
            unset($config['customOptions']);
        }

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function settingsAttributes(): array
    {
        $attributes = parent::settingsAttributes();
        $attributes[] = 'options';
        $attributes[] = 'customOptions';
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
        $hasInvalidColors = false;
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

            if (static::$optionColors && !empty($option['color'])) {
                $option['color'] = ColorValidator::normalizeColor($option['color']);
                $validator = new ColorValidator();
                if (!$validator->validate($option['color'])) {
                    $hasInvalidColors = true;
                    $option['color'] = [
                        'value' => $option['color'],
                        'hasErrors' => true,
                    ];
                }
            }
        }

        if ($hasDuplicateLabels) {
            $this->addError('options', Craft::t('app', 'All option labels must be unique.'));
        }
        if ($hasDuplicateValues) {
            $this->addError('options', Craft::t('app', 'All option values must be unique.'));
        }
        if ($hasInvalidColors) {
            $this->addError('options', Craft::t('app', 'All color values must be valid.'));
        }
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
        if (static::$optgroups) {
            $cols['isOptgroup'] = [
                'heading' => Craft::t('app', 'Optgroup?'),
                'type' => 'checkbox',
                'class' => 'thin',
                'toggle' => ['!value', '!icon', '!color', '!default'],
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
        if (static::$optionIcons) {
            $cols['icon'] = [
                'heading' => Craft::t('app', 'Icon'),
                'type' => 'icon',
                'class' => 'thin',
            ];
        }
        if (static::$optionColors) {
            $cols['color'] = [
                'heading' => Craft::t('app', 'Color'),
                'type' => 'color',
            ];
        }
        $cols['default'] = [
            'heading' => Craft::t('app', 'Default?'),
            'type' => 'checkbox',
            'radioMode' => !static::$multi,
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

        $html = Cp::editableTableFieldHtml([
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
            'data' => ['error-key' => 'options'],
        ]);

        if (static::$allowCustomOptions) {
            $html .= Cp::lightswitchFieldHtml([
                'label' => Craft::t('app', 'Allow custom options'),
                'id' => 'custom-options',
                'name' => 'customOptions',
                'on' => $this->customOptions,
            ]);
        }

        return $html;
    }

    /**
     * @inheritdoc
     */
    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        if ($value instanceof MultiOptionsFieldData || $value instanceof SingleOptionFieldData) {
            return $value;
        }

        if (is_string($value) && (
                str_starts_with($value, '[') ||
                str_starts_with($value, '{')
            )) {
            $value = Json::decodeIfJson($value);
        } elseif ($value === '' && static::$multi) {
            $value = [];
        } elseif (is_string($value) && strtolower($value) === '__blank__') {
            $value = '';
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

        $selectedBlankOption = false;
        /** @var OptionData[] $options */
        $options = [];
        $optionValues = [];
        foreach ($this->options() as $option) {
            if (!isset($option['optgroup'])) {
                $selected = $this->isOptionSelected($option, $value, $selectedValues, $selectedBlankOption);
                $options[] = new OptionData(
                    $option['label'],
                    $option['value'],
                    $selected,
                    true,
                    $option['icon'] ?? null,
                    $option['color'] ?? null,
                );
                $optionValues[] = (string)$option['value'];
            }
        }

        if (static::$multi) {
            // Convert the value to a MultiOptionsFieldData object
            $selectedOptions = [];
            foreach ($selectedValues as $selectedValue) {
                $index = array_search($selectedValue, $optionValues, true);
                $valid = $index !== false;
                $label = $valid ? $options[$index]->label : null;
                $icon = $valid ? $options[$index]->icon : null;
                $color = $valid ? $options[$index]->color : null;
                $selectedOptions[] = new OptionData($label, $selectedValue, true, $valid, $icon, $color);
            }
            $value = new MultiOptionsFieldData($selectedOptions);
        } elseif (!empty($selectedValues)) {
            // Convert the value to a SingleOptionFieldData object
            $selectedValue = reset($selectedValues);
            $index = array_search($selectedValue, $optionValues, true);
            $valid = $index !== false;
            $label = $valid ? $options[$index]->label : null;
            $icon = $valid ? $options[$index]->icon : null;
            $color = $valid ? $options[$index]->color : null;
            $value = new SingleOptionFieldData($label, $selectedValue, true, $valid, $icon, $color);
        } else {
            $value = new SingleOptionFieldData(null, null, true, false);
        }

        $value->setOptions($options);

        return $value;
    }

    /**
     * Check if given option should be marked as selected.
     *
     * @param array $option
     * @param mixed $value
     * @param array $selectedValues
     * @param bool $selectedBlankOption
     * @return bool
     */
    protected function isOptionSelected(array $option, mixed $value, array &$selectedValues, bool &$selectedBlankOption): bool
    {
        return in_array($option['value'], $selectedValues, true);
    }

    /**
     * @inheritdoc
     */
    public function serializeValue(mixed $value, ?ElementInterface $element): mixed
    {
        if ($value instanceof MultiOptionsFieldData) {
            $serialized = [];
            // Build the list out in the original option order
            foreach ($value->getOptions() as $option) {
                if ($option->selected) {
                    $serialized[] = $option->value;
                }
            }

            if ($this->customOptions) {
                foreach ($value as $option) {
                    /** @var OptionData $option */
                    if (!$option->valid && $option->value !== null && $option->value !== '') {
                        $serialized[] = $option->value;
                    }
                }
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

        if (static::$multi) {
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
     */
    public function getElementValidationRules(): array
    {
        return [
            [
                function(ElementInterface $element) {
                    $value = $element->getFieldValue($this->handle);
                    $options = $value instanceof MultiOptionsFieldData ? $value : [$value];
                    if (ArrayHelper::contains($options, fn(OptionData $option) => !$option->valid)) {
                        $element->addError($this->handle, Craft::t('yii', '{attribute} is invalid.', [
                            'attribute' => Craft::t('site', $this->name),
                        ]));
                    }
                },
                'when' => fn() => !$this->customOptions,
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function isValueEmpty(mixed $value, ElementInterface $element): bool
    {
        if ($value instanceof MultiOptionsFieldData) {
            return count($value) === 0;
        }

        return $value->value === null || $value->value === '';
    }

    /**
     * @inheritdoc
     */
    public function getPreviewHtml(mixed $value, ElementInterface $element): string
    {
        if (static::$multi) {
            /** @var MultiOptionsFieldData $value */
            $labels = [];

            foreach ($value as $option) {
                /** @var OptionData $option */
                if (!$this->isValueEmpty($option, $element)) {
                    // Custom values have no label
                    $labels[] = $option->label ? Craft::t('site', (string)$option->label) : (string)$option->value;
                }
            }

            return implode(', ', $labels);
        }

        /** @var SingleOptionFieldData $value */
        if (!$this->isValueEmpty($value, $element)) {
            $parts = [];
            if (isset($value->icon)) {
                $parts[] = Html::tag('div', Cp::iconSvg($value->icon), [
                    'class' => ['cp-icon', 'small'],
                    'style' => array_filter([
                        '--icon-color' => $value->color,
                    ]),
                ]);
            } elseif (isset($value->color)) {
                $parts[] = Html::beginTag('div', ['class' => ['color', 'small', 'static']]) .
                    Html::tag('div', options: [
                        'class' => 'color-preview',
                        'style' => [
                            'background-color' => $value->color,
                        ],
                    ]) .
                    Html::endTag('div');
            }
            // Custom values have no label
            $parts[] = Html::tag('div', $value->label ? Craft::t('site', (string)$value->label) : (string)$value->value);

            return Html::beginTag('div', ['class' => ['flex', 'flex-inline', 'gap-xs']])
                . implode('', $parts)
                . Html::endTag('div');
        }

        return '';
    }

    /**
     * @inheritdoc
     */
    public function previewPlaceholderHtml(mixed $value, ?ElementInterface $element): string
    {
        $options = array_values(array_filter($this->options, fn($option) => !empty($option['value'])));

        if (empty($options)) {
            return Craft::t('app', 'Option Label');
        }

        $labels[] = $options[0]['label'];

        if (static::$multi) {
            $labels[] = array_pop($options)['label'];
        }

        return implode(', ', $labels);
    }

    /**
     * Returns whether the field type supports storing multiple selected options.
     *
     * @return bool
     * @see multi
     */
    public function getIsMultiOptionsField(): bool
    {
        return static::$multi;
    }

    /**
     * @inheritdoc
     * @since 3.3.0
     */
    public function getContentGqlType(): Type|array
    {
        return [
            'name' => $this->handle,
            'type' => static::$multi ? Type::listOf(Type::string()) : Type::string(),
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
            'type' => static::$multi ? Type::listOf(Type::string()) : Type::string(),
            'description' => implode("\n\n", array_filter([
                $this->instructions,
                Craft::t('app', 'The allowed values are [{values}]', ['values' => implode(', ', $values)]),
            ])),
        ];
    }

    /**
     * Returns the label for the Options setting.
     *
     * @return string
     */
    protected function optionsSettingLabel(): string
    {
        return Craft::t('app', 'Options');
    }

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

        // Fire a 'defineOptions' event
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
                    'color' => static::$optionColors && !empty($option['color']) ? $option['color'] : null,
                    'icon' => static::$optionIcons && (!empty($option['icon']) || ($option['icon'] ?? null) === '0') ? $option['icon'] : null,
                ];
            }
        }

        if ($this->customOptions) {
            $selectedOptions = $value instanceof MultiOptionsFieldData ? $value : [$value];
            foreach ($selectedOptions as $option) {
                /** @var OptionData $option */
                if (!$option->valid) {
                    $translatedOptions[] = [
                        'label' => $option->value,
                        'value' => $option->value,
                        'custom' => true,
                    ];
                }
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
            if (!$value->valid) {
                return $value->value;
            }

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
        if (static::$multi) {
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
