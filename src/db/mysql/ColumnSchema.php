<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\db\mysql;

class ColumnSchema extends \yii\db\mysql\ColumnSchema
{
    /**
     * @inheritdoc
     */
    protected function typecast($value)
    {
        // Prevent Yii from typecasting our custom text column types to null
        // (https://github.com/craftcms/cms/issues/2953)
        if (
            $value === '' &&
            in_array($this->type, [
                Schema::TYPE_TINYTEXT,
                Schema::TYPE_MEDIUMTEXT,
                Schema::TYPE_LONGTEXT
            ], true)
        ) {
            return '';
        }

        return parent::typecast($value);
    }
}
