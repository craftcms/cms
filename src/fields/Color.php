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
use craft\base\InlineEditableFieldInterface;
use craft\base\MergeableFieldInterface;
use craft\elements\Entry;
use craft\fields\data\ColorData;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;
use craft\helpers\Html;
use craft\helpers\StringHelper;
use craft\validators\ColorValidator;
use Illuminate\Support\Arr;
use yii\db\Schema;

/**
 * Color represents a Color field.
 *
 * @property string|null $defaultColor
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Color extends Field implements InlineEditableFieldInterface, MergeableFieldInterface, CrossSiteCopyableFieldInterface
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Color');
    }

    /**
     * @inheritdoc
     */
    public static function icon(): string
    {
        return 'palette';
    }

    /**
     * @inheritdoc
     */
    public static function phpType(): string
    {
        return sprintf('\\%s|null', ColorData::class);
    }

    /**
     * @inheritdoc
     */
    public static function dbType(): string
    {
        return sprintf('%s(7)', Schema::TYPE_CHAR);
    }

    /**
     * @var array Color palette
     * @phpstan-var array{color:string,label:string|null,default:bool|null}[]
     * @since 5.6.0
     */
    public array $palette = [];

    /**
     * @var bool Allow custom culors
     * @since 5.6.0
     */
    public bool $allowCustomColors = false;

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        // presets => palette
        if (array_key_exists('presets', $config) || array_key_exists('defaultColor', $config)) {
            $defaultColor = ArrayHelper::remove($config, 'defaultColor');
            $config['palette'] = array_map(fn(string $color) => [
                'color' => $color,
                'label' => null,
                'default' => ($color === $defaultColor),
            ], ArrayHelper::remove($config, 'presets') ?? []);
        }

        if (isset($config['palette'])) {
            $config['palette'] = array_map(
                fn(array $color) => [
                    'color' => $color['color'] ? ColorValidator::normalizeColor($color['color']) : null,
                ] + $color,
                $config['palette']
            );
        }

        // Default allowCustomColors to true for existing fields
        if (isset($config['id']) && !isset($config['allowCustomColors'])) {
            $config['allowCustomColors'] = true;
        }

        parent::__construct($config);
    }

    /**
     * Returns the default color
     *
     * @return string|null
     * @since 5.6.0
     */
    public function getDefaultColor(): ?string
    {
        $color = Arr::first($this->palette, fn(array $color) => $color['default'] ?? false);
        return $color ? $color['color'] : null;
    }

    /**
     * Sets the default color
     *
     * @param string|null $defaultColor
     * @since 5.6.0
     */
    public function setDefaultValue(?string $defaultColor): void
    {
        $this->palette = Arr::map($this->palette, fn(array $color) => ['default' => false] + $color);

        if ($defaultColor) {
            $defaultColor = ColorValidator::normalizeColor($defaultColor);
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
     * @deprecated in 5.6.0
     */
    public function getPresets(): array
    {
        return array_values(array_filter(array_map(fn(array $color) => $color['color'], $this->palette)));
    }

    /**
     * @param string[] $presets
     * @deprecated in 5.6.0
     */
    public function setPresets(array $presets): void
    {
        $this->palette = array_map(
            fn(string $color) => ['color' => $color, 'label' => null, 'default' => false],
            $presets,
        );
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml(): ?string
    {
        return $this->settingsHtml(false);
    }

    /**
     * @inheritdoc
     */
    public function getReadOnlySettingsHtml(): ?string
    {
        return $this->settingsHtml(true);
    }

    private function settingsHtml(bool $readOnly): string
    {
        return
            Cp::editableTableFieldHtml([
                'label' => Craft::t('app', 'Palette'),
                'name' => 'palette',
                'instructions' => Craft::t('app', 'Define the available colors to choose from.'),
                'cols' => [
                    'color' => [
                        'type' => 'color',
                        'heading' => Craft::t('app', 'Color'),
                    ],
                    'label' => [
                        'type' => 'singleline',
                        'heading' => Craft::t('app', 'Label'),
                    ],
                    'default' => [
                        'type' => 'checkbox',
                        'heading' => Craft::t('app', 'Default'),
                        'radioMode' => true,
                    ],
                ],
                'rows' => $this->palette,
                'allowAdd' => true,
                'allowReorder' => true,
                'allowDelete' => true,
                'addRowLabel' => Craft::t('app', 'Add a color'),
                'errors' => $this->getErrors('palette'),
                'data' => ['error-key' => 'palette'],
                'static' => $readOnly,
            ]) .
            Cp::lightswitchFieldHtml([
                'label' => Craft::t('app', 'Allow custom colors'),
                'id' => 'allow-custom-colors',
                'name' => 'allowCustomColors',
                'on' => $this->allowCustomColors,
                'disabled' => $readOnly,
            ]);
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        return [
            ...parent::defineRules(),
            [
                ['palette'],
                'required',
                'when' => fn() => !$this->allowCustomColors,
                'message' => Craft::t('app', 'Palette cannot be blank if custom colors aren’t allowed.'),
            ],
            [['palette'], function() {
                $validator = new ColorValidator();
                foreach ($this->palette as $color) {
                    if (!$validator->validate($color['color'], $error)) {
                        $this->addError('palette', Craft::t('yii', '{attribute} is invalid.', [
                            'attribute' => StringHelper::ensureLeft($color['color'] ?? '', '#'),
                        ]));
                    }
                }
            }],
        ];
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
            $defaultColor = $this->getDefaultColor();
            if ($defaultColor) {
                $value = $defaultColor;
            }
        }

        $value = trim($value);

        if (!$value || $value === '#') {
            return null;
        }

        $value = ColorValidator::normalizeColor($value);
        $value = new ColorData($value);

        // set the label on the value too?
        $option = Arr::first($this->palette, fn(array $color) => $color['color'] === $value->getHex());
        if (isset($option['label']) && $option['label'] !== '') {
            $value->label = $option['label'];
        }

        return $value;
    }

    /**
     * @inheritdoc
     */
    public function getElementValidationRules(): array
    {
        return [
            ColorValidator::class,
            [
                function(ElementInterface $element) {
                    if (!$this->allowCustomColors) {
                        /** @var ColorData $value */
                        $value = $element->getFieldValue($this->handle);
                        if (!ArrayHelper::contains($this->palette, fn(array $color) => $color['color'] === $value->getHex())) {
                            $element->addError("field:$this->handle", Craft::t('yii', '{attribute} is invalid.', [
                                'attribute' => $this->getUiLabel(),
                            ]));
                        }
                    }
                },
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        $id = $this->getInputId();

        if (empty($this->palette)) {
            return Cp::colorHtml([
                'id' => $id,
                'describedBy' => $this->describedBy,
                'name' => $this->handle,
                'value' => $value?->getHex(),
            ]);
        }

        /** @var ColorData|null $value */
        $isInPalette = (
            $value &&
            ArrayHelper::contains($this->palette, fn(array $color) => $color['color'] === $value->getHex())
        );
        $isCustom = (
            $value &&
            $this->allowCustomColors &&
            !$isInPalette
        );
        $showBlankOption = (
            !$value ||
            !$this->layoutElement->required ||
            (!$this->allowCustomColors && !$isInPalette)
        );

        $html =
            Html::beginTag('div', [
                'class' => ['flex', 'flex-col', 'items-stretch'],
                'style' => [
                    'width' => '25em',
                    'max-width' => '100%',
                ],
            ]) .
            Cp::colorSelectFieldHtml([
                'id' => $id,
                'labelledBy' => $this->getLabelId(),
                'describedBy' => $this->describedBy,
                'class' => 'fullwidth',
                'name' => "$this->handle[color]",
                'options' => array_filter([
                    ...array_map(
                        fn(array $color) => [
                            'label' => isset($color['label']) && $color['label'] !== ''
                                ? Craft::t('site', $color['label'])
                                : $color['color'],
                            'value' => $color['color'],
                        ],
                        $this->palette,
                    ),
                    $this->allowCustomColors ? [
                        'label' => Craft::t('app', 'Custom…'),
                        'value' => '__custom__',
                    ] : null,
                ]),
                'withBlankOption' => $showBlankOption,
                'value' => $isInPalette ? $value->getHex() : ($isCustom ? '__custom__' : '__blank__'),
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
                        !$isCustom ? 'hidden' : null,
                    ]),
                    'style' => [
                        'width' => '25em',
                        'max-width' => '100%',
                        'padding-inline' => '9px',
                    ],
                ]) .
                Html::label(Craft::t('app', 'Custom color:'), "$id-custom-input", [
                    'id' => $customLabelId,
                ]) .
                Cp::colorHtml([
                    'id' => "$id-custom-input",
                    'labelledBy' => $customLabelId,
                    'describedBy' => $this->describedBy,
                    'name' => "$this->handle[custom]",
                    'value' => $isCustom ? $value->getHex() : null,
                ]) .
                Html::endTag('div');
        } elseif ($value && !$isInPalette) {
            Craft::$app->getView()->setInitialDeltaValue($this->handle, $value->getHex());
        }

        $html .= Html::endTag('div');

        return $html;
    }

    /**
     * @inheritdoc
     */
    public function getStaticHtml(mixed $value, ElementInterface $element): string
    {
        /** @var ColorData|null $value */
        if (!$value) {
            return '';
        }

        $html = Html::beginTag('div', ['class' => ['color', 'noteditable']]) .
            Html::tag('div', options: [
                'class' => 'color-preview',
                'style' => ['background-color' => $value->getHex()],
            ]) .
            Html::endTag('div');

        if (isset($value->label)) {
            $html .= Html::tag('div', Html::encode($value->label), ['class' => 'colorhex']);
        } else {
            $html .= Html::tag('div', $value->getHex(), ['class' => ['colorhex', 'code']]);
        }

        return $html;
    }

    /**
     * @inheritdoc
     */
    public function getPreviewHtml(mixed $value, ElementInterface $element): string
    {
        /** @var ColorData|null $value */
        if (!$value) {
            return Html::beginTag('div', ['class' => ['color', 'small', 'static']]) .
                Html::tag('div', options: ['class' => 'color-preview']) .
                Html::endTag('div');
        }

        $html = Html::beginTag('div', ['class' => ['color', 'small', 'static']]) .
            Html::tag('div', options: [
                'class' => 'color-preview',
                'style' => [
                    'background-color' => $value->getHex(),
                ],
            ]) .
            Html::endTag('div');

        if (isset($value->label)) {
            $html .= Html::tag('span', Html::encode($value->label), ['class' => 'colorhex']);
        } else {
            $html .= Html::tag('div', $value->getHex(), ['class' => ['colorhex', 'code']]);
        }

        return $html;
    }

    /**
     * @inheritdoc
     */
    public function previewPlaceholderHtml(mixed $value, ?ElementInterface $element): string
    {
        if (!$value) {
            if (empty($this->palette)) {
                $value = new ColorData(sprintf('#%06X', mt_rand(0, 0xFFFFFF)));
            } else {
                $example = $this->palette[array_rand($this->palette)];
                $value = new ColorData($example['color']);
                if (isset($example['label']) && $example['label'] !== '') {
                    $value->label = $example['label'];
                }
            }
        }

        return $this->getPreviewHtml($value, $element ?? new Entry());
    }
}
