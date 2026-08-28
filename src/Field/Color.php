<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Contracts\CrossSiteCopyableFieldInterface;
use CraftCms\Cms\Field\Contracts\DefaultableFieldInterface;
use CraftCms\Cms\Field\Contracts\InlineEditableFieldInterface;
use CraftCms\Cms\Field\Contracts\MergeableFieldInterface;
use CraftCms\Cms\Field\Data\ColorData;
use CraftCms\Cms\Form\Contracts\Control;
use CraftCms\Cms\Form\Controls\Color as ColorControl;
use CraftCms\Cms\Form\Controls\Lightswitch;
use CraftCms\Cms\Form\Controls\Table;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\Nodes\Field as FormField;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\DeltaRegistry;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Query;
use CraftCms\Cms\Validation\Rules\ColorRule;
use Deprecated;
use Illuminate\Support\Collection;
use Illuminate\Validation\ConditionalRules;
use Illuminate\Validation\Rule;
use Override;

use function CraftCms\Cms\t;

/**
 * Color represents a Color field.
 *
 * @property string|null $defaultColor
 */
class Color extends Field implements CrossSiteCopyableFieldInterface, DefaultableFieldInterface, InlineEditableFieldInterface, MergeableFieldInterface
{
    #[Override]
    public static function displayName(): string
    {
        return t('Color');
    }

    #[Override]
    public static function icon(): string
    {
        return 'palette';
    }

    #[Override]
    public static function phpType(): string
    {
        return sprintf('\\%s|null', ColorData::class);
    }

    #[Override]
    public static function dbType(): string
    {
        return sprintf('%s(7)', Query::TYPE_CHAR);
    }

    /**
     * @var array Color palette
     *
     * @phpstan-var array{color:string,label:string|null,default:bool|null}[]
     */
    public array $palette = [];

    /**
     * @var bool Allow custom culors
     */
    public bool $allowCustomColors = false;

    public function __construct($config = [])
    {
        // presets => palette
        if (array_key_exists('presets', $config) || array_key_exists('defaultColor', $config)) {
            $defaultColor = Arr::pull($config, 'defaultColor');
            $config['palette'] = array_map(fn (string $color) => [
                'color' => $color,
                'label' => null,
                'default' => ($color === $defaultColor),
            ], Arr::pull($config, 'presets', []));
        }

        if (isset($config['palette'])) {
            $config['palette'] = array_map(
                fn (array $color) => [
                    'color' => $color['color'] ? ColorRule::normalizeColor($color['color']) : null,
                ] + $color,
                $config['palette'],
            );
        }

        // Default allowCustomColors to true for existing fields
        if (isset($config['id']) && ! isset($config['allowCustomColors'])) {
            $config['allowCustomColors'] = true;
        }

        parent::__construct($config);
    }

    #[Deprecated(message: 'in 5.10.0. [[getDefaultValue()]] should be used instead.')]
    public function getDefaultColor(): ?string
    {
        return $this->getDefaultValue();
    }

    public function getDefaultValue(): ?string
    {
        $color = Arr::first($this->palette, fn (array $color) => $color['default'] ?? false);

        return $color ? $color['color'] : null;
    }

    #[Override]
    public function settingsForm(FormContext $context = new FormContext): Form
    {
        return Form::make([
            FormField::make(t('Palette'))
                ->instructions(t('Define the available colors to choose from.'))
                ->control(Table::make('palette')
                    ->columns([
                        'color' => ['heading' => t('Color'), 'type' => 'color'],
                        'label' => ['heading' => t('Label'), 'type' => 'singleline'],
                        'default' => ['heading' => t('Default'), 'type' => 'checkbox', 'radioMode' => true],
                    ])
                    ->allowAdd()
                    ->allowDelete()
                    ->allowReorder()
                    ->value($this->palette)),
            FormField::make(t('Allow custom colors'))
                ->control(Lightswitch::make('allowCustomColors')->value($this->allowCustomColors)),
        ]);
    }

    #[Override]
    public function formControl(FieldContext $context): Control
    {
        return ColorControl::make($context->path)
            ->presets(array_values(array_filter(array_column($this->palette, 'color'))))
            ->value($context->value instanceof ColorData ? $context->value->getHex() : $context->value);
    }

    /**
     * Sets the default color
     */
    public function setDefaultValue(?string $defaultColor): void
    {
        $this->palette = Arr::map($this->palette, fn (array $color) => ['default' => false] + $color);

        if ($defaultColor) {
            $defaultColor = ColorRule::normalizeColor($defaultColor);
            foreach ($this->palette as $color) {
                if (($color['color'] ?? null) === $defaultColor) {
                    $color['default'] = true;

                    return;
                }
            }
        }

        // If we're still here, the default color didn’t exist in the palette
        $this->palette[] = ['color' => $defaultColor, 'label' => null, 'default' => true];
    }

    /**
     * @return string[]
     */
    #[Deprecated(message: 'in 5.6.0')]
    public function getPresets(): array
    {
        return array_values(array_filter(array_map(fn (array $color) => $color['color'], $this->palette)));
    }

    /**
     * @param  string[]  $presets
     */
    #[Deprecated(message: 'in 5.6.0')]
    public function setPresets(array $presets): void
    {
        $this->palette = array_map(
            fn (string $color) => ['color' => $color, 'label' => null, 'default' => false],
            $presets,
        );
    }

    #[Override]
    public function getRules(): array
    {
        return array_merge(parent::getRules(), [
            'allowCustomColors' => ['required', 'boolean'],
            'palette' => ['nullable', 'required_if:allowCustomColors,true'],
            'palette.*.label' => ['string'],
            'palette.*.default' => ['nullable', 'boolean'],
            'palette.*.color' => [new ColorRule],
        ]);
    }

    #[Override]
    public function getMessages(): array
    {
        return [
            'palette.required_if' => t('Palette cannot be blank if custom colors aren’t allowed.'),
        ];
    }

    #[Override]
    public function useFieldset(): bool
    {
        return true;
    }

    #[Override]
    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        if ($value instanceof ColorData) {
            return $value;
        }

        if (is_array($value)) {
            if (($value['color'] ?? null) !== '__custom__') {
                $value = $value['color'];
            } else {
                $value = $value['custom'] ?? null;
            }
        }

        if ($value === '__blank__') {
            return null;
        }

        // If this is a new entry, look for any default options
        if ($value === null && $this->isFresh($element)) {
            $defaultColor = $this->getDefaultValue();
            if ($defaultColor) {
                $value = $defaultColor;
            }
        }

        $value = trim((string) $value);

        if (! $value || $value === '#') {
            return null;
        }

        $value = ColorRule::normalizeColor($value);
        $value = new ColorData($value);

        // set the label on the value too?
        $option = Arr::first($this->palette, fn (array $color) => $color['color'] === $value->getHex());
        if (isset($option['label']) && $option['label'] !== '') {
            $value->label = $option['label'];
        }

        return $value;
    }

    /** @return list<ColorRule|ConditionalRules> */
    #[Override]
    public function getElementRules(ElementInterface $element): array
    {
        return [
            new ColorRule,
            Rule::when(! $this->allowCustomColors, [
                function ($attribute, ColorData $value, $fail) {
                    if (Collection::make($this->palette)->doesntContain(fn (array $color) => $color['color'] === $value->getHex())) {
                        $fail(t('{attribute} is invalid.', [
                            'attribute' => $this->getUiLabel(),
                        ]));
                    }
                },
            ]),
        ];
    }

    #[Override]
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        $id = $this->getInputId();

        if (empty($this->palette)) {
            return FormFields::colorHtml([
                'id' => $id,
                'describedBy' => $this->describedBy,
                'name' => $this->handle,
                'value' => $value?->getHex(),
            ]);
        }

        /** @var ColorData|null $value */
        $isInPalette = (
            $value &&
            Collection::make($this->palette)->contains('color', $value->getHex())
        );
        $isCustom = (
            $value &&
            $this->allowCustomColors &&
            ! $isInPalette
        );
        $showBlankOption = (
            ! $value ||
            ! $this->layoutElement->required ||
            (! $this->allowCustomColors && ! $isInPalette)
        );

        $html =
            Html::beginTag('div', [
                'class' => ['cp:flex', 'cp:flex-col', 'cp:items-stretch'],
                'style' => [
                    'width' => '25em',
                    'max-width' => '100%',
                ],
            ]).
            FormFields::colorSelectFieldHtml([
                'id' => $id,
                'labelledBy' => $this->getLabelId(),
                'describedBy' => $this->describedBy,
                'class' => 'fullwidth',
                'name' => "$this->handle[color]",
                'options' => array_filter([
                    ...array_map(
                        fn (array $color) => [
                            'label' => isset($color['label']) && $color['label'] !== ''
                                ? t($color['label'], category: 'site')
                                : $color['color'],
                            'value' => $color['color'],
                        ],
                        $this->palette,
                    ),
                    $this->allowCustomColors ? [
                        'label' => t('Custom…'),
                        'value' => '__custom__',
                    ] : null,
                ]),
                'withBlankOption' => $showBlankOption,
                'value' => match (true) {
                    $isInPalette => $value->getHex(),
                    $isCustom => '__custom__',
                    default => '__blank__',
                },
                'toggle' => $this->allowCustomColors,
                'targetPrefix' => $this->allowCustomColors ? "$id-custom-" : null,
            ]);

        if ($this->allowCustomColors) {
            $customLabelId = "$id-custom-label";
            $html .=
                Html::beginTag('div', [
                    'id' => "$id-custom-__custom__",
                    'class' => array_filter([
                        'pane',
                        'hairline',
                        'py-s',
                        'mt-0',
                        'flex',
                        'flex-inline',
                        ! $isCustom ? 'hidden' : null,
                    ]),
                    'style' => [
                        'width' => '25em',
                        'max-width' => '100%',
                        'padding-inline' => '9px',
                    ],
                ]).
                Html::label(t('Custom color:'), "$id-custom-input")
                    ->id($customLabelId).
                FormFields::colorHtml([
                    'id' => "$id-custom-input",
                    'labelledBy' => $customLabelId,
                    'describedBy' => $this->describedBy,
                    'name' => "$this->handle[custom]",
                    'value' => $isCustom ? $value->getHex() : null,
                ]).
                Html::endTag('div');
        } elseif ($value && ! $isInPalette) {
            DeltaRegistry::setInitialValue($this->handle, $value->getHex());
        }

        return $html.Html::endTag('div');
    }

    #[Override]
    public function getPreviewHtml(mixed $value, ElementInterface $element): string
    {
        /** @var ColorData|null $value */
        if (! $value) {
            return '';
        }

        $html = Html::beginTag('div', ['class' => ['color', 'small', 'cp:static']]).
            Html::tag('div', attributes: [
                'class' => 'color-preview',
                'style' => [
                    'background-color' => $value->getHex(),
                ],
            ]).
            Html::endTag('div');

        if (isset($value->label)) {
            $html .= Html::tag('span', Html::encode($value->label), ['class' => 'colorhex']);
        } else {
            $html .= Html::tag('div', $value->getHex(), ['class' => ['colorhex', 'code']]);
        }

        return $html;
    }

    #[Override]
    public function previewPlaceholderHtml(mixed $value, ?ElementInterface $element): string
    {
        if (! $value) {
            if (empty($this->palette)) {
                $value = new ColorData(sprintf('#%06X', random_int(0, 0xFFFFFF)));
            } else {
                $example = $this->palette[array_rand($this->palette)];
                $value = new ColorData($example['color']);
                if (isset($example['label']) && $example['label'] !== '') {
                    $value->label = $example['label'];
                }
            }
        }

        return $this->getPreviewHtml($value, $element ?? new Entry);
    }
}
