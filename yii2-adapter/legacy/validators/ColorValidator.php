<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\validators;

use yii\base\UnknownPropertyException;
use yii\validators\RegularExpressionValidator;

/**
 * Color hex validator
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class ColorValidator extends RegularExpressionValidator
{
    /**
     * Normalizes a color value.
     *
     * @param string $color
     * @return string
     */
    public static function normalizeColor(string $color): string
    {
        // lowercase
        $color = strtolower($color);

        if ($color === 'transparent') {
            return $color;
        }

        // make sure it starts with a #
        if ($color[0] !== '#') {
            $color = '#' . $color;
        }

        // #abc => #aabbcc, #abcd => #aabbccdd
        $length = strlen($color);
        if ($length === 4 || $length === 5) {
            $color = '#' . implode('', array_map(fn(string $char) => $char . $char, str_split(substr($color, 1))));
        }

        // #aabbccff => #aabbcc
        if (strlen($color) === 9 && str_ends_with($color, 'ff')) {
            $color = substr($color, 0, 7);
        }

        return $color;
    }

    /**
     * @inheritdoc
     */
    public $pattern = '/^(?:#[0-9a-f]{6}|transparent)$/';

    /**
     * @var bool Whether 8-digit hex values with an alpha channel should be allowed.
     * @since 5.12.0
     */
    public bool $allowAlpha = false;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        if ($this->allowAlpha) {
            $this->pattern = '/^(?:#[0-9a-f]{6}(?:[0-9a-f]{2})?|transparent)$/';
        }

        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function validateAttribute($model, $attribute): void
    {
        $original = $value = $model->$attribute;

        if (is_string($value)) {
            $value = self::normalizeColor($value);
        }

        $result = $this->validateValue($value);
        if (!empty($result)) {
            $this->addError($model, $attribute, $result[0], $result[1]);
        } elseif ($value !== $original) {
            // update the model with the normalized value
            try {
                $model->$attribute = $value;
            } catch (UnknownPropertyException) {
                // fine, validate the original value
                parent::validateAttribute($model, $attribute);
            }
        }
    }
}
