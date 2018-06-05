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
        // Fix for https://github.com/yiisoft/yii2/issues/16364
        if (
            $this->allowNull === false &&
            $value === '' &&
            !in_array(
                $this->type,
                [
                    Schema::TYPE_TEXT,
                    Schema::TYPE_STRING,
                    Schema::TYPE_BINARY,
                    Schema::TYPE_CHAR
                ],
                true)
        ) {
            return '';
        }

        return parent::typecast($value);
    }
}
