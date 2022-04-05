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
use yii\db\ActiveQueryInterface;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * Class Volume record.
 *
 * @property int $id ID
 * @property int|null $fieldLayoutId Field layout ID
 * @property string $name Name
 * @property string $handle Handle
 * @property string $fs The filesystem handle, or an environment variable that references it
 * @property string $transformFs The transform filesystem handle, or an environment variable that references it
 * @property string $transformSubpath The transform filesystem subpath for storing transforms
 * @property string $titleTranslationMethod
 * @property string|null $titleTranslationKeyFormat
 * @property int $sortOrder Sort order
 * @property FieldLayout $fieldLayout Field layout
 * @mixin SoftDeleteBehavior
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Volume extends ActiveRecord
{
    use SoftDeleteTrait;

    /**
     * @inheritdoc
     * @return string
     */
    public static function tableName(): string
    {
        return Table::VOLUMES;
    }

    /**
     * Returns the asset volume’s fieldLayout.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getFieldLayout(): ActiveQueryInterface
    {
        return $this->hasOne(FieldLayout::class, ['id' => 'fieldLayoutId']);
    }
}
