<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\fields;

use Craft;
use craft\app\base\Field;
use craft\app\base\PreviewableFieldInterface;
use craft\app\helpers\Html;
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
    public static function displayName()
    {
        return Craft::t('app', 'Color');
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getContentColumnType()
    {
        return Schema::TYPE_STRING.'(7)';
    }

    /**
     * @inheritdoc
     */
    public function getInputHtml($value, $element)
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
    public function getStaticHtml($value, $element)
    {
        if ($value) {
            return Html::encodeParams('<div class="color" style="cursor: default;"><div class="colorpreview" style="background-color: {bgColor};"></div></div><div class="colorhex code">{bgColor}</div>',
                [
                    'bgColor' => $value
                ]);
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function getTableAttributeHtml($value, $element)
    {
        if ($value && $value != '#000000') {
            return '<div class="color small static"><div class="colorpreview" style="background-color: '.$value.';"></div></div>'.
            '<div class="colorhex code">'.$value.'</div>';
        } else {
            return '';
        }
    }
}
