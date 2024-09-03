<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\records;

use craft\db\ActiveRecord;
use craft\db\SoftDeleteTrait;
use craft\db\Table;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * Field layout record class.
 *
 * @property int $id ID
 * @property string $type Type
 * @property array $config Config
 * @mixin SoftDeleteBehavior
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class FieldLayout extends ActiveRecord
{
    use SoftDeleteTrait;

    /**
     * @inheritdoc
     * @return string
     */
    public static function tableName(): string
    {
        return Table::FIELDLAYOUTS;
    }
}
