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
class Color extends Field implements PreviewableFieldInterface
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
    public static function valueType(): string
    {
        return sprintf('\\%s|null', ColorData::class);
    }

    /**
     * @var string|null The default color hex
     */
    public ?string $defaultColor = null;

    /**
     * @inheritdoc
     */
    public function getContentColumnType(): string
    {
        return Schema::TYPE_STRING . '(7)';
    }

    /** @inheritdoc */
    public function getSettingsHtml(): ?string
    {
        return Cp::colorFieldHtml([
            'label' => Craft::t('app', 'Default Color'),
            'id' => 'default-color',
            'name' => 'defaultColor',
            'value' => $this->defaultColor,
            'errors' => $this->getErrors('defaultColor'),
        ]);
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['defaultColor'], ColorValidator::class];
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
    public function normalizeValue(mixed $value, ?ElementInterface $element = null): mixed
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
    protected function inputHtml(mixed $value, ?ElementInterface $element = null): string
    {
        /** @var ColorData|null $value */
        return Craft::$app->getView()->renderTemplate('_includes/forms/color.twig', [
            'id' => $this->getInputId(),
            'describedBy' => $this->describedBy,
            'name' => $this->handle,
            'value' => $value?->getHex(),
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
            '<div class="color" style="cursor: default;"><div class="color-preview" style="background-color: {bgColor};"></div></div><div class="colorhex code">{bgColor}</div>',
            [
                'bgColor' => $value->getHex(),
            ]);
    }

    /**
     * @inheritdoc
     */
    public function getTableAttributeHtml(mixed $value, ElementInterface $element): string
    {
        /** @var ColorData|null $value */
        if (!$value) {
            return '<div class="color small static"><div class="color-preview"></div></div>';
        }

        return "<div class='color small static'><div class='color-preview' style='background-color: {$value->getHex()};'></div></div>" .
            "<div class='colorhex code'>{$value->getHex()}</div>";
    }
}
