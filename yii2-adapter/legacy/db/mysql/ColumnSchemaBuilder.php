<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\db\mysql;

use Craft;
use yii\db\mysql\ColumnSchemaBuilder as YiiColumnSchemaBuilder;

/**
 * @inheritdoc
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class ColumnSchemaBuilder extends YiiColumnSchemaBuilder
{
    /**
     * @inheritdoc
     */
    protected function buildLengthString(): string
    {
        if ($this->type == Schema::TYPE_ENUM) {
            $schema = Craft::$app->getDb()->getSchema();
            $str = '(';
            foreach ($this->length as $i => $value) {
                if ($i != 0) {
                    $str .= ',';
                }
                $str .= $schema->quoteValue($value);
            }
            $str .= ')';

            return $str;
        }

        return parent::buildLengthString();
    }
}
