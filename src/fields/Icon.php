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
use craft\base\ThumbableFieldInterface;
use craft\helpers\Cp;
use craft\helpers\Html;
use yii\db\Schema;

/**
 * Icon represents an icon picker field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
class Icon extends Field implements InlineEditableFieldInterface, ThumbableFieldInterface
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Icon');
    }

    /**
     * @inheritdoc
     */
    public static function icon(): string
    {
        return 'icons';
    }

    /**
     * @inheritdoc
     */
    public static function phpType(): string
    {
        return 'string|null';
    }

    /**
     * @inheritdoc
     */
    public static function dbType(): string
    {
        return Schema::TYPE_STRING;
    }

    /**
     * @inheritdoc
     */
    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        return $value ?: null;
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return Cp::iconPickerHtml([
            'id' => $this->getInputId(),
            'describedBy' => $this->describedBy,
            'name' => $this->handle,
            'value' => $value,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getStaticHtml(mixed $value, ElementInterface $element): string
    {
        return Cp::iconPickerHtml([
            'static' => true,
            'value' => $value,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getPreviewHtml(mixed $value, ElementInterface $element): string
    {
        return $value ? Html::tag('div', Cp::iconSvg($value), ['class' => 'cp-icon']) : '';
    }

    /**
     * @inheritdoc
     */
    public function getThumbHtml(mixed $value, ElementInterface $element, int $size): ?string
    {
        return $value ? Html::tag('div', Cp::iconSvg($value), ['class' => 'cp-icon']) : null;
    }
}
