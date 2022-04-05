<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\db;

use craft\helpers\Db;
use DateTime;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * Soft-delete trait.
 *
 * This should be implemented by Active Record classes that wish to support soft deletes.
 * With it, Active Query objects returned by [[\yii\db\ActiveRecord::find()]] will exclude
 * any soft-deleted rows.
 *
 * The database table should be created with a `dateDeleted` column (type `datetime null`).
 *
 * ```php
 * 'dateDeleted' => $this->dateTime()->null()
 * ```
 *
 * To fetch all rows, including soft-deleted ones, call [[findWithTrashed()]] instead of `find()`.
 *
 * ```php
 * $records = MyActiveRecord::findWithTrashed()->all();
 * ```
 *
 * To fetch only soft-deleted rows, call [[findTrashed()]] instead of `find()`.
 *
 * ```php
 * $records = MyActiveRecord::findTrashed()->all();
 * ```
 *
 * Active Record classes that use this trait and also have their own
 * [[\yii\db\BaseActiveRecord::behaviors()|behaviors]] should rename this trait’s
 * [[behaviors()]] method when using the trait, and then call it from the `behaviors()` method.
 *
 * ```php
 * use SoftDeleteTrait {
 *     behaviors as softDeleteBehaviors;
 * }
 *
 * public function behaviors(): array
 * {
 *     $behaviors = $this->softDeleteBehaviors();
 *     $behaviors['myBehavior'] = MyBehavior::class;
 *     return $behaviors;
 * }
 * ```
 *
 * Active Record classes that implement a custom `find()` method will need to manually
 * add a condition to exclude soft-deleted rows.
 *
 * ```php
 * public static function find(): ElementQueryInterface
 * {
 *     // @var MyActiveQuery $query
 *     $query = Craft::createObject(MyActiveQuery::class, [static::class]);
 *     $query->where(['dateDeleted' => null]);
 *     return $query;
 * }
 * ```
 *
 * @property string|null $dateDeleted Date deleted
 * @mixin ActiveRecord
 * @mixin SoftDeleteBehavior
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.1.0
 */
trait SoftDeleteTrait
{
    /**
     * @return ActiveQuery
     */
    public static function find(): ActiveQuery
    {
        $query = parent::find();
        $column = sprintf('%s.dateDeleted', $query->getAlias());
        return $query->where([$column => null]);
    }

    /**
     * @return ActiveQuery
     */
    public static function findWithTrashed(): ActiveQuery
    {
        return static::find()->where([]);
    }

    /**
     * @return ActiveQuery
     */
    public static function findTrashed(): ActiveQuery
    {
        return static::find()->where(['not', ['dateDeleted' => null]]);
    }

    /**
     * @inheritdoc
     */
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['softDelete'] = [
            'class' => SoftDeleteBehavior::class,
            'softDeleteAttributeValues' => [
                'dateDeleted' => function() {
                    return Db::prepareDateForDb(new DateTime());
                },
            ],
        ];
        return $behaviors;
    }

    /**
     * This method is called at the beginning of restoring a record.
     */
    public function beforeRestore(): bool
    {
        $this->prepareForDb();
        return true;
    }
}
