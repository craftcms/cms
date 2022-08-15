<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use craft\base\FieldInterface;

/**
 * Class FieldHelper
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.7.5
 */
class FieldHelper
{
    /**
     * Ensures that the given field has a column suffix set on it, if it should have one.
     *
     * @param FieldInterface $field
     */
    public static function ensureColumnSuffix(FieldInterface $field): void
    {
        if (
            !$field->columnSuffix &&
            $field::hasContentColumn() &&
            ($field->getIsNew() || is_array($field->getContentColumnType()))
        ) {
            $field->columnSuffix = StringHelper::randomString(8);
        }
    }
}
