<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use CraftCms\Cms\Cp\Icons;
use CraftCms\Cms\Database\Expressions\JsonContains;
use CraftCms\Cms\Database\QueryParam;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Field\Conditions\OptionsFieldConditionRule;
use CraftCms\Cms\Field\Contracts\CrossSiteCopyableFieldInterface;
use CraftCms\Cms\Field\Contracts\DefaultableFieldInterface;
use CraftCms\Cms\Field\Contracts\MergeableFieldInterface;
use CraftCms\Cms\Field\Contracts\PreviewableFieldInterface;
use CraftCms\Cms\Field\Data\MultiOptionsFieldData;
use CraftCms\Cms\Field\Data\OptionData;
use CraftCms\Cms\Field\Data\SingleOptionFieldData;
use CraftCms\Cms\Field\Events\InputOptionsResolving;
use CraftCms\Cms\Form\Contracts\Control;
use CraftCms\Cms\Form\Controls\Choice;
use CraftCms\Cms\Form\Controls\Lightswitch;
use CraftCms\Cms\Form\Controls\Table;
use CraftCms\Cms\Form\Enums\ChoicePresentation;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\Nodes\Field as FormField;
use CraftCms\Cms\Gql\Arguments\OptionField as OptionFieldArguments;
use CraftCms\Cms\Gql\Resolvers\OptionField as OptionFieldResolver;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Query;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Validation\Rules\ColorRule;
use GraphQL\Type\Definition\Type;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Validation\Validator;
use Override;

use function CraftCms\Cms\t;

/**
 * BaseOptionsField is the base class for classes representing an options field.
 *
 * @phpstan-import-type ArgumentConfig from \GraphQL\Type\Definition\Argument
 *
 * @phpstan-type Option array{label:string, value:string, default?:bool|string, icon?:string|null, color?:string|null}
 * @phpstan-type Optgroup array{optgroup:string}
 */
abstract class BaseOptionsField extends Field implements CrossSiteCopyableFieldInterface, DefaultableFieldInterface, MergeableFieldInterface, PreviewableFieldInterface
{
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

    #[Override]
    public static function phpType(): string
    {
        return sprintf('\\%s', static::$multi ? MultiOptionsFieldData::class : SingleOptionFieldData::class);
    }

    #[Override]
    public static function dbType(): string
    {
        return static::$multi ? Query::TYPE_JSON : Query::TYPE_STRING;
    }

    #[Override]
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

        $valueSql = self::valueColumn($instances);

        if ($valueSql === null) {
            return $query;
        }

        $isEmptyValueParam = fn (mixed $value): bool => is_string($value) &&
            in_array(strtolower($value), [':empty:', ':notempty:', 'not :empty:'], true);

        $applyConditions = function (Builder $query) use ($param, $valueSql, $isEmptyValueParam) {
            foreach ($param->values as $value) {
                if ($isEmptyValueParam($value)) {
                    $query->whereParam($valueSql, $value, columnType: Query::TYPE_JSON, boolean: $param->operator);

                    continue;
                }

                is_string($valueSql)
                    ? $query->whereJsonContains($valueSql, $value, boolean: $param->operator)
                    : $query->where(new JsonContains($valueSql, $value), boolean: $param->operator);
            }
        };

        if ($negate && Collection::make($param->values)->doesntContain($isEmptyValueParam)) {
            return $query->where(function (Builder $query) use ($valueSql, $applyConditions) {
                $query->whereNull($valueSql)
                    ->orWhereNot($applyConditions);
            });
        }

        return $query->where($applyConditions, boolean: $negate ? 'and not' : 'and');
    }

    /** @param list<static> $instances */
    private static function valueColumn(array $instances): string|Expression|null
    {
        if (count($instances) === 1 && isset($instances[0]->layoutElement)) {
            return "elements_sites.content->{$instances[0]->layoutElement->uid}";
        }

        return static::valueSql($instances);
    }

    /** @var list<Option|Optgroup> The available options */
    public array $options;

    /**
     * @var bool Whether a custom option is allowed.
     */
    public bool $customOptions = false;

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

    #[Override]
    public function settingsAttributes(): array
    {
        $attributes = parent::settingsAttributes();
        $attributes[] = 'options';
        $attributes[] = 'customOptions';

        return $attributes;
    }

    #[Override]
    public function getRules(): array
    {
        return array_merge(parent::getRules(), [
            'options' => ['array'],
        ]);
    }

    public function afterValidate(?Validator $validator = null): void
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

    #[Override]
    public function settingsForm(FormContext $context = new FormContext): Form
    {
        $columns = [];
        if (static::$optgroups) {
            $columns['isOptgroup'] = [
                'heading' => t('Optgroup?'),
                'type' => 'checkbox',
                'class' => 'thin',
                'toggle' => ['!value', '!icon', '!color', '!default'],
            ];
        }
        $columns['label'] = [
            'heading' => t('Option Label'),
            'type' => 'singleline',
            'autopopulate' => 'value',
        ];
        $columns['value'] = [
            'heading' => t('Value'),
            'type' => 'singleline',
            'class' => 'code',
        ];
        if (static::$optionIcons) {
            $columns['icon'] = ['heading' => t('Icon'), 'type' => 'icon', 'class' => 'thin'];
        }
        if (static::$optionColors) {
            $columns['color'] = ['heading' => t('Color'), 'type' => 'color'];
        }
        $columns['default'] = [
            'heading' => t('Default?'),
            'type' => 'checkbox',
            'radioMode' => ! static::$multi,
            'class' => 'thin',
        ];

        $rows = array_map(function (array $option): array {
            if (isset($option['optgroup'])) {
                $option['isOptgroup'] = true;
                $option['label'] = Arr::pull($option, 'optgroup');
            }

            return $option;
        }, $this->options ?: [['label' => '', 'value' => '']]);

        return Form::make([
            FormField::make($this->optionsSettingLabel())
                ->instructions(t('Define the available options.'))
                ->control(Table::make('options')
                    ->columns($columns)
                    ->allowAdd()
                    ->allowDelete()
                    ->allowReorder()
                    ->value($rows)),
        ])->when(
            static::$allowCustomOptions,
            fn (Form $form): Form => $form->add(
                FormField::make(t('Allow custom options'))
                    ->control(Lightswitch::make('customOptions')->value($this->customOptions)),
            ),
        );
    }

    #[Override]
    public function formControl(FieldContext $context): Control
    {
        $options = collect($this->translatedOptions(true, $context->value, $context->element))
            ->filter(fn (array $option): bool => array_key_exists('value', $option))
            ->map(fn (array $option): array => Arr::only($option, ['label', 'value', 'disabled']))
            ->values()
            ->all();

        return Choice::make($context->path)
            ->options($options)
            ->multiple(static::$multi)
            ->presentation($this->formPresentation())
            ->value($this->encodeValue($context->value));
    }

    protected function formPresentation(): ChoicePresentation
    {
        return static::$multi ? ChoicePresentation::Checkboxes : ChoicePresentation::Select;
    }

    /** @return list<string>|string|null */
    #[Override]
    public function getDefaultValue(): array|string|null
    {
        return $this->defaultValue();
    }

    #[Override]
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
     *
     * @param  Option  $option
     * @param  list<string>  $selectedValues
     */
    protected function isOptionSelected(array $option, mixed $value, array &$selectedValues, bool &$selectedBlankOption): bool
    {
        return in_array($option['value'], $selectedValues, true);
    }

    #[Override]
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

            // return null if there are no selected options
            // (see https://github.com/craftcms/cms/pull/19019)
            return $serialized ?: null;
        }

        return parent::serializeValue($value, $element);
    }

    #[Override]
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

    public function getElementConditionRuleType(): array|string
    {
        return OptionsFieldConditionRule::class;
    }

    /** @return list<\Closure> */
    #[Override]
    public function getElementRules(ElementInterface $element): array
    {
        if (! $this->customOptions) {
            return [];
        }

        return [
            function ($attribute, $value, $fail) {
                $options = $value instanceof MultiOptionsFieldData ? $value : [$value];

                if (Collection::make($options)->contains(fn (OptionData $option) => ! $option->valid)) {
                    $fail(t('{attribute} is invalid.', [
                        'attribute' => t($this->name, category: 'site'),
                    ]));
                }
            },
        ];
    }

    #[Override]
    public function isValueEmpty(mixed $value, ElementInterface $element): bool
    {
        if ($value instanceof MultiOptionsFieldData) {
            return count($value) === 0;
        }

        return $value->value === null || $value->value === '';
    }

    #[Override]
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
                $parts[] = Html::tag('div', Icons::svg($value->icon), [
                    'class' => ['cp-icon', 'small'],
                    'style' => array_filter([
                        '--icon-color' => $value->color,
                    ]),
                ]);
            } elseif (isset($value->color)) {
                $parts[] = Html::beginTag('div', ['class' => ['color', 'small', 'cp:static']]).
                    Html::tag('div', attributes: [
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

            return Html::beginTag('div', ['class' => ['cp:flex', 'flex-inline', 'cp:gap-sm']])
                .implode('', $parts)
                .Html::endTag('div');
        }

        return '';
    }

    #[Override]
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

        $labels = array_map(Html::encode(...), $labels);

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
     * @return array{
     *     name:string|null,
     *     type:Type,
     *     args:array<string, ArgumentConfig>,
     *     resolve:string,
     * }
     */
    #[Override]
    public function getContentGqlType(): array
    {
        return [
            'name' => $this->handle,
            'type' => static::$multi ? Type::listOf(Type::string()) : Type::string(),
            'args' => OptionFieldArguments::getArguments(),
            'resolve' => OptionFieldResolver::class.'::resolve',
        ];
    }

    /** @return Type|array{name:string, type:Type, description:string} */
    #[Override]
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
     *
     * @return list<Option|Optgroup>
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
     * @return list<array{optgroup:string}|array{label:string|null, value:string|list<string>, color?:string|null, icon?:string|null, custom?:true}>
     */
    protected function translatedOptions(bool $encode = false, mixed $value = null, ?ElementInterface $element = null): array
    {
        $options = $this->options();
        $translatedOptions = [];

        event($event = new InputOptionsResolving(
            field: $this,
            options: $options,
            value: $value,
            element: $element,
        ));

        foreach ($event->options as $option) {
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

    /** @return string|list<string>|null Base64-encodes a value. */
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
