<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\db;

use Craft;
use craft\helpers\Db;
use yii\db\ActiveQuery as YiiActiveQuery;
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
 * public function behaviors()
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
 * public static function find()
 * {
 *     // @var MyActiveQuery $query
 *     $query = Craft::createObject(MyActiveQuery::class, [static::class]);
 *     $query->where(['dateDeleted' => null]);
 *     return $query;
 * }
 * ```
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.1.0
 *
 * @property ActiveRecord $this
 * @property string|null $dateDeleted Date deleted
 * @mixin SoftDeleteBehavior
 */
trait SoftDeleteTrait
{
    /**
     * @return YiiActiveQuery
     */
    public static function find()
    {
        $query = parent::find();

        // todo: remove schema version condition after next beakpoint
        $schemaVersion = Craft::$app->getInstalledSchemaVersion();
        if (version_compare($schemaVersion, '3.1.19', '>=')) {
            if ($query instanceof ActiveQuery) {
                $alias = $query->getAlias();
                $column = "$alias.dateDeleted";
            } else {
                $column = 'dateDeleted';
            }
            $query->where([$column => null]);
        }

        return $query;
    }

    /**
     * @return YiiActiveQuery
     */
    public static function findWithTrashed(): YiiActiveQuery
    {
        return static::find()->where([]);
    }

    /**
     * @return YiiActiveQuery
     */
    public static function findTrashed(): YiiActiveQuery
    {
        return static::find()->where(['not', ['dateDeleted' => null]]);
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['softDelete'] = [
            'class' => SoftDeleteBehavior::class,
            'softDeleteAttributeValues' => [
                'dateDeleted' => function() {
                    return Db::prepareDateForDb(new \DateTime());
                }
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
