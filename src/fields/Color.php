<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\fields;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\base\PreviewableFieldInterface;
use craft\helpers\Html;
use yii\db\Schema;

/**
 * Color represents a Color field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Color extends Field implements PreviewableFieldInterface
{
    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Color');
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getContentColumnType(): string
    {
        return Schema::TYPE_STRING.'(7)';
    }

    /**
     * @inheritdoc
     */
    public function getInputHtml($value, ElementInterface $element = null): string
    {
        // Default to black, so the JS-based color picker is consistent with Chrome
        if (!$value) {
            $value = '#000000';
        }

        return Craft::$app->getView()->renderTemplate('_includes/forms/color', [
            'id' => Craft::$app->getView()->formatInputId($this->handle),
            'name' => $this->handle,
            'value' => $value,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getStaticHtml($value, ElementInterface $element): string
    {
        if (!$value) {
            return '';
        }

        return Html::encodeParams(
            '<div class="color" style="cursor: default;"><div class="colorpreview" style="background-color: {bgColor};"></div></div><div class="colorhex code">{bgColor}</div>',
            [
                'bgColor' => $value
            ]);
    }

    /**
     * @inheritdoc
     */
    public function getTableAttributeHtml($value, ElementInterface $element): string
    {
        if (!$value || $value === '#000000') {
            return '';
        }

        return '<div class="color small static"><div class="colorpreview" style="background-color: '.$value.';"></div></div>'.
            '<div class="colorhex code">'.$value.'</div>';
    }
}
