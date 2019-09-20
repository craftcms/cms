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
 * @since 3.0
 */
class ColumnSchemaBuilder extends YiiColumnSchemaBuilder
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->categoryMap[Schema::TYPE_TINYTEXT] = self::CATEGORY_STRING;
        $this->categoryMap[Schema::TYPE_MEDIUMTEXT] = self::CATEGORY_STRING;
        $this->categoryMap[Schema::TYPE_LONGTEXT] = self::CATEGORY_STRING;
        $this->categoryMap[Schema::TYPE_ENUM] = self::CATEGORY_STRING;
    }

    /**
     * @inheritdoc
     */
    protected function buildLengthString()
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
