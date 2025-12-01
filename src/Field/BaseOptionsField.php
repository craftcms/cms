<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use craft\base\ElementInterface;
use craft\fields\conditions\OptionsFieldConditionRule;
use craft\gql\arguments\OptionField as OptionFieldArguments;
use craft\gql\resolvers\OptionField as OptionFieldResolver;
use craft\helpers\Cp;
use CraftCms\Cms\Database\QueryParam;
use CraftCms\Cms\Field\Contracts\CrossSiteCopyableFieldInterface;
use CraftCms\Cms\Field\Contracts\MergeableFieldInterface;
use CraftCms\Cms\Field\Contracts\PreviewableFieldInterface;
use CraftCms\Cms\Field\Data\MultiOptionsFieldData;
use CraftCms\Cms\Field\Data\OptionData;
use CraftCms\Cms\Field\Data\SingleOptionFieldData;
use CraftCms\Cms\Field\Events\DefineInputOptions;
use CraftCms\Cms\Shared\Rules\ColorRule;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Str;
use GraphQL\Type\Definition\Type;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Validation\Validator;
use yii\db\Schema;

use function CraftCms\Cms\t;

/**
 * BaseOptionsField is the base class for classes representing an options field.
 */
abstract class BaseOptionsField extends Field implements CrossSiteCopyableFieldInterface, MergeableFieldInterface, PreviewableFieldInterface
{
    /**
     * @event {@see DefineInputOptions} Event triggered when defining the options for the field's input.
     */
    public const string EVENT_DEFINE_OPTIONS = 'defineOptions';

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
     */
    protected static bool $optionIcons = false;

    /**
     * @var bool Whether field options should include a color setting
     */
    protected static bool $optionColors = false;

    /**
     * @var bool Whether the field should allow adding a custom option
     */
    protected static bool $allowCustomOptions = false;

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function phpType(): string
    {
        return sprintf('\\%s', static::$multi ? MultiOptionsFieldData::class : SingleOptionFieldData::class);
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function dbType(): string
    {
        return static::$multi ? Schema::TYPE_JSON : Schema::TYPE_STRING;
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function modifyQuery(Builder $query, array $instances, mixed $value): Builder
    {
        if (! static::$multi) {
            return parent::modifyQuery($query, $instances, $value);
        }

        $param = QueryParam::parse($value);

        if (empty($param->values)) {
            return $query;
        }

        if ($param->operator === QueryParam::NOT) {
            $param->operator = QueryParam::OR;
            $negate = true;
        } else {
            $negate = false;
        }

        $valueSql = static::valueSql($instances);

        return $query->where(function (Builder $query) use ($param, $valueSql) {
            foreach ($param->values as $value) {
                if (
                    is_string($value) &&
                    in_array(strtolower($value), [':empty:', ':notempty:', 'not :empty:'])
                ) {
                    $query->whereParam($valueSql, $value, columnType: Schema::TYPE_JSON, boolean: $param->operator);

                    continue;
                }

                $query->whereJsonContains($valueSql, $value, $param->operator);
            }
        }, boolean: $negate ? 'and not' : 'and');
    }

    /**
     * @var array The available options
     */
    public array $options;

    /**
     * @var bool Whether a custom option is allowed.
     */
    public bool $customOptions = false;

    /**
     * {@inheritdoc}
     */
    public function __construct($config = [])
    {
        // Normalize the options
        $options = [];
        if (isset($config['options']) && is_array($config['options'])) {
            foreach ($config['options'] as $key => $option) {
                // Old school?
                if (! is_array($option)) {
                    $options[] = [
                        'label' => $option,
                        'value' => $key,
                        'default' => '',
                    ];
                } elseif (! empty($option['isOptgroup'])) {
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

        if (! static::$allowCustomOptions) {
            unset($config['customOptions']);
        }

        parent::__construct($config);
    }

    /**
     * {@inheritdoc}
     */
    public function settingsAttributes(): array
    {
        $attributes = parent::settingsAttributes();
        $attributes['options'] = $this->options;
        $attributes['customOptions'] = $this->customOptions;

        return $attributes;
    }

    #[\Override]
    public static function getRules(): array
    {
        return array_merge(parent::getRules(), [
            'options' => ['array'],
        ]);
    }

    public function afterValidate(Validator $validator): void
    {
        $labels = [];
        $values = [];
        $hasDuplicateLabels = false;
        $hasDuplicateValues = false;
        $hasInvalidColors = false;
        $optgroup = '__root__';

        foreach ($this->options as $option) {
            // Ignore optgroups
            if (array_key_exists('optgroup', $option)) {
                $optgroup = $option['optgroup'];

                continue;
            }

            $label = (string) $option['label'];
            $value = (string) $option['value'];

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

            if (static::$optionColors && ! empty($option['color'])) {
                $option['color'] = ColorRule::normalizeColor($option['color']);

                $colorValidator = ValidatorFacade::make(
                    data: ['color' => $option['color']],
                    rules: ['color' => new ColorRule],
                );

                if ($colorValidator->fails()) {
                    $hasInvalidColors = true;
                    $option['color'] = [
                        'value' => $option['color'],
                        'hasErrors' => true,
                    ];
                }
            }
        }

        if ($hasDuplicateLabels) {
            $validator->errors()->add('options', t('All option labels must be unique.'));
        }

        if ($hasDuplicateValues) {
            $validator->errors()->add('options', t('All option values must be unique.'));
        }

        if ($hasInvalidColors) {
            $validator->errors()->add('options', t('All color values must be valid.'));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getSettingsHtml(): string
    {
        if (empty($this->options)) {
            // Give it a default row
            $this->options = [['label' => '', 'value' => '']];
        }

        $cols = [];
        if (static::$optgroups) {
            $cols['isOptgroup'] = [
                'heading' => t('Optgroup?'),
                'type' => 'checkbox',
                'class' => 'thin',
                'toggle' => ['!value', '!icon', '!color', '!default'],
            ];
        }
        $cols['label'] = [
            'heading' => t('Option Label'),
            'type' => 'singleline',
            'autopopulate' => 'value',
        ];
        $cols['value'] = [
            'heading' => t('Value'),
            'type' => 'singleline',
            'class' => 'code',
        ];
        if (static::$optionIcons) {
            $cols['icon'] = [
                'heading' => t('Icon'),
                'type' => 'icon',
                'class' => 'thin',
            ];
        }
        if (static::$optionColors) {
            $cols['color'] = [
                'heading' => t('Color'),
                'type' => 'color',
            ];
        }
        $cols['default'] = [
            'heading' => t('Default?'),
            'type' => 'checkbox',
            'radioMode' => ! static::$multi,
            'class' => 'thin',
        ];

        $rows = [];
        foreach ($this->options as $option) {
            if (isset($option['optgroup'])) {
                $option['isOptgroup'] = true;
                $option['label'] = Arr::pull($option, 'optgroup');
            }
            $rows[] = $option;
        }

        $html = Cp::editableTableFieldHtml([
            'label' => $this->optionsSettingLabel(),
            'instructions' => t('Define the available options.'),
            'id' => 'options',
            'name' => 'options',
            'addRowLabel' => t('Add an option'),
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
                'label' => t('Allow custom options'),
                'id' => 'custom-options',
                'name' => 'customOptions',
                'on' => $this->customOptions,
            ]);
        }

        return $html;
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
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
        foreach ((array) $value as $val) {
            $val = (string) $val;
            if (str_starts_with($val, 'base64:')) {
                $val = base64_decode(Str::chopStart($val, 'base64:'));
            }
            $selectedValues[] = $val;
        }

        $selectedBlankOption = false;
        /** @var OptionData[] $options */
        $options = [];
        $optionValues = [];
        foreach ($this->options() as $option) {
            if (! isset($option['optgroup'])) {
                $selected = $this->isOptionSelected($option, $value, $selectedValues, $selectedBlankOption);
                $options[] = new OptionData(
                    $option['label'],
                    $option['value'],
                    $selected,
                    true,
                    $option['icon'] ?? null,
                    $option['color'] ?? null,
                );
                $optionValues[] = (string) $option['value'];
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
        } elseif (! empty($selectedValues)) {
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
     */
    protected function isOptionSelected(array $option, mixed $value, array &$selectedValues, bool &$selectedBlankOption): bool
    {
        return in_array($option['value'], $selectedValues, true);
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
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
                    if (! $option->valid && $option->value !== null && $option->value !== '') {
                        $serialized[] = $option->value;
                    }
                }
            }

            return $serialized;
        }

        return parent::serializeValue($value, $element);
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
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
     * {@inheritdoc}
     */
    public function getElementConditionRuleType(): array|string
    {
        return OptionsFieldConditionRule::class;
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function getElementValidationRules(): array
    {
        return [
            [
                function (ElementInterface $element) {
                    $value = $element->getFieldValue($this->handle);
                    $options = $value instanceof MultiOptionsFieldData ? $value : [$value];
                    if (Collection::make($options)->contains(fn (OptionData $option) => ! $option->valid)) {
                        $element->addError($this->handle, t('{attribute} is invalid.', [
                            'attribute' => t($this->name, category: 'site'),
                        ]));
                    }
                },
                'when' => fn () => ! $this->customOptions,
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function isValueEmpty(mixed $value, ElementInterface $element): bool
    {
        if ($value instanceof MultiOptionsFieldData) {
            return count($value) === 0;
        }

        return $value->value === null || $value->value === '';
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function getPreviewHtml(mixed $value, ElementInterface $element): string
    {
        if (static::$multi) {
            /** @var MultiOptionsFieldData $value */
            $labels = [];

            foreach ($value as $option) {
                /** @var OptionData $option */
                if (! $this->isValueEmpty($option, $element)) {
                    // Custom values have no label
                    $labels[] = $option->label
                        ? t((string) $option->label, category: 'site')
                        : (string) $option->value;
                }
            }

            return implode(', ', $labels);
        }

        /** @var SingleOptionFieldData $value */
        if (! $this->isValueEmpty($value, $element)) {
            $parts = [];
            if (isset($value->icon)) {
                $parts[] = Html::tag('div', Cp::iconSvg($value->icon), [
                    'class' => ['cp-icon', 'small'],
                    'style' => array_filter([
                        '--icon-color' => $value->color,
                    ]),
                ]);
            } elseif (isset($value->color)) {
                $parts[] = Html::beginTag('div', ['class' => ['color', 'small', 'static']]).
                    Html::tag('div', options: [
                        'class' => 'color-preview',
                        'style' => [
                            'background-color' => $value->color,
                        ],
                    ]).
                    Html::endTag('div');
            }
            // Custom values have no label
            $parts[] = Html::tag('div', $value->label
                ? t((string) $value->label, category: 'site')
                : (string) $value->value
            );

            return Html::beginTag('div', ['class' => ['flex', 'flex-inline', 'gap-xs']])
                .implode('', $parts)
                .Html::endTag('div');
        }

        return '';
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function previewPlaceholderHtml(mixed $value, ?ElementInterface $element): string
    {
        $options = array_values(array_filter($this->options, fn ($option) => ! empty($option['value'])));

        if (empty($options)) {
            return t('Option Label');
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
     * @see multi
     */
    public function getIsMultiOptionsField(): bool
    {
        return static::$multi;
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function getContentGqlType(): array
    {
        return [
            'name' => $this->handle,
            'type' => static::$multi ? Type::listOf(Type::string()) : Type::string(),
            'args' => OptionFieldArguments::getArguments(),
            'resolve' => OptionFieldResolver::class.'::resolve',
        ];
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function getContentGqlMutationArgumentType(): Type|array
    {
        $values = [];

        foreach ($this->options as $option) {
            if (! isset($option['optgroup'])) {
                $values[] = '“'.$option['value'].'”';
            }
        }

        return [
            'name' => $this->handle,
            'type' => static::$multi ? Type::listOf(Type::string()) : Type::string(),
            'description' => implode("\n\n", array_filter([
                $this->instructions,
                t('The allowed values are [{values}]', ['values' => implode(', ', $values)]),
            ])),
        ];
    }

    /**
     * Returns the label for the Options setting.
     */
    protected function optionsSettingLabel(): string
    {
        return t('Options');
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
     */
    protected function options(): array
    {
        return $this->options ?? [];
    }

    /**
     * Returns the field options, with labels run through t().
     *
     * @param  bool  $encode  Whether the option values should be base64-encoded
     * @param  mixed  $value  The field’s value. This will either be the [[normalizeValue()|normalized value]],
     *                        raw POST data (i.e. if there was a validation error), or null
     * @param  ElementInterface|null  $element  The element the field is associated with, if there is one
     */
    protected function translatedOptions(bool $encode = false, mixed $value = null, ?ElementInterface $element = null): array
    {
        $options = $this->options();
        $translatedOptions = [];

        // Fire a 'defineOptions' event
        if ($this->hasComponentListeners(self::EVENT_DEFINE_OPTIONS)) {
            $event = new DefineInputOptions(
                field: $this,
                options: $options,
                value: $value,
                element: $element,
            );
            $this->dispatchComponentEvent(self::EVENT_DEFINE_OPTIONS, $event);
            $options = $event->options;
        }

        foreach ($options as $option) {
            if (isset($option['optgroup'])) {
                $translatedOptions[] = [
                    'optgroup' => t($option['optgroup'], category: 'site'),
                ];
            } else {
                $translatedOptions[] = [
                    'label' => t($option['label'], category: 'site'),
                    'value' => $encode ? $this->encodeValue($option['value']) : $option['value'],
                    'color' => static::$optionColors && ! empty($option['color']) ? $option['color'] : null,
                    'icon' => static::$optionIcons && (! empty($option['icon']) || ($option['icon'] ?? null) === '0') ? $option['icon'] : null,
                ];
            }
        }

        if ($this->customOptions) {
            $selectedOptions = $value instanceof MultiOptionsFieldData ? $value : [$value];
            foreach ($selectedOptions as $option) {
                /** @var OptionData $option */
                if (! $option->valid) {
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
     */
    protected function encodeValue(OptionData|MultiOptionsFieldData|string|null $value): string|array|null
    {
        if ($value instanceof MultiOptionsFieldData) {
            /** @var OptionData[] $options */
            $options = (array) $value;

            return array_map($this->encodeValue(...), $options);
        }

        if ($value instanceof OptionData) {
            if (! $value->valid) {
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
                if (! empty($option['default'])) {
                    $defaultValues[] = $option['value'];
                }
            }

            return $defaultValues;
        }

        foreach ($this->options() as $option) {
            if (! empty($option['default'])) {
                return $option['value'];
            }
        }

        return null;
    }
}
