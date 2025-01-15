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
use craft\base\InlineEditableFieldInterface;
use craft\base\MergeableFieldInterface;
use craft\elements\Entry;
use craft\fields\data\ColorData;
use craft\helpers\Cp;
use craft\helpers\Html;
use craft\validators\ColorValidator;
use yii\db\Schema;

/**
 * Color represents a Color field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Color extends Field implements InlineEditableFieldInterface, MergeableFieldInterface
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
     * @var string[] Preset colors
     * @since 4.8.0
     */
    public array $presets = [];

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        if (isset($config['presets'])) {
            $config['presets'] = array_values(array_filter(array_map(
                fn($color) => is_array($color) ? $color['color'] : $color,
                $config['presets']
            )));
            // Normalize afterward so empty strings have been filtered out
            $config['presets'] = array_map(
                fn(string $color) => ColorValidator::normalizeColor($color),
                $config['presets']
            );
        }

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public static function dbType(): string
    {
        return sprintf('%s(7)', Schema::TYPE_CHAR);
    }

    /**
     * @var string|null The default color hex
     */
    public ?string $defaultColor = null;

    /** @inheritdoc */
    public function getSettingsHtml(): ?string
    {
        return Cp::colorFieldHtml([
            'label' => Craft::t('app', 'Default Color'),
            'id' => 'default-color',
            'name' => 'defaultColor',
            'value' => $this->defaultColor,
            'errors' => $this->getErrors('defaultColor'),
            'data' => ['error-key' => 'defaultColor'],
        ]) .
            Cp::editableTableFieldHtml([
                'label' => Craft::t('app', 'Presets'),
                'name' => 'presets',
                'instructions' => Craft::t('app', 'Choose colors which should be recommended by the color picker.'),
                'cols' => [
                    'color' => [
                        'type' => 'color',
                        'heading' => Craft::t('app', 'Color'),
                    ],
                ],
                'rows' => array_map(fn(string $color) => compact('color'), $this->presets),
                'allowAdd' => true,
                'allowReorder' => true,
                'allowDelete' => true,
                'addRowLabel' => Craft::t('app', 'Add a color'),
                'inputContainerAttributes' => [
                    'style' => [
                        'max-width' => '15em',
                    ],
                ],
                'errors' => $this->getErrors('presets'),
                'data' => ['error-key' => 'presets'],
            ]);
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['defaultColor'], ColorValidator::class];

        $rules[] = [['presets'], function() {
            $validator = new ColorValidator();
            foreach ($this->presets as $color) {
                if (!$validator->validate($color, $error)) {
                    $this->addError('presets', Craft::t('yii', '{attribute} is invalid.', [
                        'attribute' => "#$color",
                    ]));
                }
            }
        }];

        return $rules;
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

        // If this is a new entry, look for any default options
        if ($value === null && $this->isFresh($element) && $this->defaultColor) {
            $value = $this->defaultColor;
        }

        $value = trim($value);

        if (!$value || $value === '#') {
            return null;
        }

        $value = ColorValidator::normalizeColor($value);
        return new ColorData($value);
    }

    /**
     * @inheritdoc
     */
    public function getElementValidationRules(): array
    {
        return [
            ColorValidator::class,
        ];
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        /** @var ColorData|null $value */
        return Craft::$app->getView()->renderTemplate('_includes/forms/color.twig', [
            'id' => $this->getInputId(),
            'describedBy' => $this->describedBy,
            'name' => $this->handle,
            'value' => $value?->getHex(),
            'presets' => $this->presets,
        ]);
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

        return Html::encodeParams(
            '<div class="color noteditable"><div class="color-preview" style="background-color: {bgColor};"></div></div><div class="colorhex code">{bgColor}</div>',
            [
                'bgColor' => $value->getHex(),
            ]);
    }

    /**
     * @inheritdoc
     */
    public function getPreviewHtml(mixed $value, ElementInterface $element): string
    {
        /** @var ColorData|null $value */
        if (!$value) {
            return '<div class="color small static"><div class="color-preview"></div></div>';
        }

        return "<div class='color small static'><div class='color-preview' style='background-color: {$value->getHex()};'></div></div>" .
            "<div class='colorhex code'>{$value->getHex()}</div>";
    }

    /**
     * @inheritdoc
     */
    public function previewPlaceholderHtml(mixed $value, ?ElementInterface $element): string
    {
        if (!$value) {
            $value = new ColorData(sprintf('#%06X', mt_rand(0, 0xFFFFFF)));
        }

        return $this->getPreviewHtml($value, $element ?? new Entry());
    }
}
