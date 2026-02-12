<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\ar\softdelete;

use yii\base\Behavior;
use yii\base\InvalidConfigException;
use yii\base\ModelEvent;
use yii\db\ActiveRecord;
use yii\db\BaseActiveRecord;
use yii\db\StaleObjectException;

/**
 * SoftDeleteBehavior provides support for "soft" delete of ActiveRecord models as well as restoring them
 * from "deleted" state.
 *
 * ```php
 * class Item extends ActiveRecord
 * {
 *     public function behaviors()
 *     {
 *         return [
 *             'softDeleteBehavior' => [
 *                 'class' => SoftDeleteBehavior::className(),
 *                 'softDeleteAttributeValues' => [
 *                     'isDeleted' => true
 *                 ],
 *             ],
 *         ];
 *     }
 * }
 * ```
 *
 * Basic usage example:
 *
 * ```php
 * $item = Item::findOne($id);
 * $item->softDelete(); // mark record as "deleted"
 *
 * $item = Item::findOne($id);
 * var_dump($item->isDeleted); // outputs "true"
 *
 * $item->restore(); // restores record from "deleted"
 *
 * $item = Item::findOne($id);
 * var_dump($item->isDeleted); // outputs "false"
 * ```
 *
 * @see SoftDeleteQueryBehavior
 *
 * @property BaseActiveRecord $owner owner ActiveRecord instance.
 * @property bool $replaceRegularDelete whether to perform soft delete instead of regular delete.
 * If enabled {@see BaseActiveRecord::delete()} will perform soft deletion instead of actual record deleting.
 * @property bool $useRestoreAttributeValuesAsDefaults whether to use {@see restoreAttributeValues} as defaults on record insertion.
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class SoftDeleteBehavior extends Behavior
{
    /**
     * @event ModelEvent an event that is triggered before deleting a record.
     * You may set {@see ModelEvent::$isValid} to be false to stop the deletion.
     */
    const EVENT_BEFORE_SOFT_DELETE = 'beforeSoftDelete';
    /**
     * @event Event an event that is triggered after a record is deleted.
     */
    const EVENT_AFTER_SOFT_DELETE = 'afterSoftDelete';
    /**
     * @event ModelEvent an event that is triggered before record is restored from "deleted" state.
     * You may set {@see ModelEvent::$isValid} to be false to stop the restoration.
     */
    const EVENT_BEFORE_RESTORE = 'beforeRestore';
    /**
     * @event Event an event that is triggered after a record is restored from "deleted" state.
     */
    const EVENT_AFTER_RESTORE = 'afterRestore';

    /**
     * @var array values of the owner attributes, which should be applied on soft delete, in format: [attributeName => attributeValue].
     * Those may raise a flag:
     *
     * ```php
     * ['isDeleted' => true]
     * ```
     *
     * or switch status:
     *
     * ```php
     * ['statusId' => Item::STATUS_DELETED]
     * ```
     *
     * Attribute value can be a callable:
     *
     * ```php
     * ['isDeleted' => function ($model) {return time()}]
     * ```
     */
    public $softDeleteAttributeValues = [
        'isDeleted' => true
    ];
    /**
     * @var array|null values of the owner attributes, which should be applied on restoration from "deleted" state,
     * in format: `[attributeName => attributeValue]`. If not set value will be automatically detected from {@see softDeleteAttributeValues}.
     */
    public $restoreAttributeValues;
    /**
     * @var bool whether to invoke owner {@see BaseActiveRecord::beforeDelete()} and {@see BaseActiveRecord::afterDelete()}
     * while performing soft delete. This option affects only {@see softDelete()} method.
     */
    public $invokeDeleteEvents = true;
    /**
     * @var callable|null callback, which execution determines if record should be "hard" deleted instead of being marked
     * as deleted. Callback should match following signature: `bool function(BaseActiveRecord $model)`
     * For example:
     *
     * ```php
     * function ($user) {
     *     return $user->lastLoginDate === null;
     * }
     * ```
     */
    public $allowDeleteCallback;
    /**
     * @var string class name of the exception, which should trigger a fallback to {@see softDelete()} method from {@see safeDelete()}.
     * By default {@see \yii\db\IntegrityException} is used, which means soft deleting will be performed on foreign constraint
     * violation DB exception.
     * You may specify another exception class here to customize fallback error level. For example: usage of {@see \Throwable}
     * will cause soft-delete fallback on any error during regular deleting.
     * @see safeDelete()
     */
    public $deleteFallbackException = 'yii\db\IntegrityException';

    /**
     * @var bool whether to perform soft delete instead of regular delete.
     * If enabled {@see BaseActiveRecord::delete()} will perform soft deletion instead of actual record deleting.
     */
    private $_replaceRegularDelete = false;

    /**
     * @var bool whether to use {@see restoreAttributeValues} as defaults on record insertion.
     * @since 1.0.4
     */
    private $_useRestoreAttributeValuesAsDefaults = false;


    /**
     * @return bool whether to perform soft delete instead of regular delete.
     */
    public function getReplaceRegularDelete()
    {
        return $this->_replaceRegularDelete;
    }

    /**
     * @param bool $replaceRegularDelete whether to perform soft delete instead of regular delete.
     */
    public function setReplaceRegularDelete($replaceRegularDelete)
    {
        $this->_replaceRegularDelete = $replaceRegularDelete;

        if (is_object($this->owner)) {
            $owner = $this->owner;
            $this->detach();
            $this->attach($owner);
        }
    }

    /**
     * @return bool whether to use {@see restoreAttributeValues} as defaults on record insertion.
     * @since 1.0.4
     */
    public function getUseRestoreAttributeValuesAsDefaults()
    {
        return $this->_useRestoreAttributeValuesAsDefaults;
    }

    /**
     * @param bool $useRestoreAttributeValuesAsDefaults whether to use {@see restoreAttributeValues} as defaults on record insertion.
     * @since 1.0.4
     */
    public function setUseRestoreAttributeValuesAsDefaults($useRestoreAttributeValuesAsDefaults)
    {
        $this->_useRestoreAttributeValuesAsDefaults = $useRestoreAttributeValuesAsDefaults;

        if (is_object($this->owner)) {
            $owner = $this->owner;
            $this->detach();
            $this->attach($owner);
        }
    }

    /**
     * Marks the owner as deleted.
     * @return int|false the number of rows marked as deleted, or false if the soft deletion is unsuccessful for some reason.
     * Note that it is possible the number of rows deleted is 0, even though the soft deletion execution is successful.
     * @throws StaleObjectException if optimistic locking is enabled and the data being updated is outdated.
     * @throws \Throwable in case soft delete failed in transactional mode.
     */
    public function softDelete()
    {
        if ($this->isDeleteAllowed()) {
            return $this->owner->delete();
        }

        $softDeleteCallback = function () {
            if ($this->invokeDeleteEvents && !$this->owner->beforeDelete()) {
                return false;
            }

            $result = $this->softDeleteInternal();

            if ($this->invokeDeleteEvents) {
                $this->owner->afterDelete();
            }

            return $result;
        };

        if (!$this->isTransactional(ActiveRecord::OP_DELETE) && !$this->isTransactional(ActiveRecord::OP_UPDATE)) {
            return call_user_func($softDeleteCallback);
        }

        $transaction = $this->beginTransaction();
        try {
            $result = call_user_func($softDeleteCallback);
            if ($result === false) {
                $transaction->rollBack();
            } else {
                $transaction->commit();
            }
            return $result;
        } catch (\Exception $exception) {
            // PHP < 7.0
        } catch (\Throwable $exception) {
            // PHP >= 7.0
        }

        $transaction->rollBack();
        throw $exception;
    }

    /**
     * Marks the owner as deleted.
     * @return int|false the number of rows marked as deleted, or false if the soft deletion is unsuccessful for some reason.
     * @throws StaleObjectException if optimistic locking is enabled and the data being updated is outdated.
     */
    protected function softDeleteInternal()
    {
        $result = false;
        if ($this->beforeSoftDelete()) {
            $attributes = $this->owner->getDirtyAttributes();
            foreach ($this->softDeleteAttributeValues as $attribute => $value) {
                if (!is_scalar($value) && is_callable($value)) {
                    $value = call_user_func($value, $this->owner);
                }
                $attributes[$attribute] = $value;
            }
            $result = $this->updateAttributes($attributes);
            $this->afterSoftDelete();
        }

        return $result;
    }

    /**
     * This method is invoked before soft deleting a record.
     * The default implementation raises the {@see EVENT_BEFORE_SOFT_DELETE} event.
     * @return bool whether the record should be deleted. Defaults to true.
     */
    public function beforeSoftDelete()
    {
        if (method_exists($this->owner, 'beforeSoftDelete')) {
            if (!$this->owner->beforeSoftDelete()) {
                return false;
            }
        }

        $event = new ModelEvent();
        $this->owner->trigger(self::EVENT_BEFORE_SOFT_DELETE, $event);

        return $event->isValid;
    }

    /**
     * This method is invoked after soft deleting a record.
     * The default implementation raises the {@see EVENT_AFTER_SOFT_DELETE} event.
     * You may override this method to do postprocessing after the record is deleted.
     * Make sure you call the parent implementation so that the event is raised properly.
     */
    public function afterSoftDelete()
    {
        if (method_exists($this->owner, 'afterSoftDelete')) {
            $this->owner->afterSoftDelete();
        }
        $this->owner->trigger(self::EVENT_AFTER_SOFT_DELETE);
    }

    /**
     * @return bool whether owner "hard" deletion allowed or not.
     */
    protected function isDeleteAllowed()
    {
        if ($this->allowDeleteCallback === null) {
            return false;
        }
        return call_user_func($this->allowDeleteCallback, $this->owner);
    }

    // Restore :

    /**
     * Restores record from "deleted" state, after it has been "soft" deleted.
     * @return int|false the number of restored rows, or false if the restoration is unsuccessful for some reason.
     * @throws StaleObjectException if optimistic locking is enabled and the data being updated is outdated.
     * @throws \Throwable in case restore failed in transactional mode.
     */
    public function restore()
    {
        $restoreCallback = function () {
            $result = false;
            if ($this->beforeRestore()) {
                $result = $this->restoreInternal();
                $this->afterRestore();
            }
            return $result;
        };

        if (!$this->isTransactional(ActiveRecord::OP_UPDATE)) {
            return call_user_func($restoreCallback);
        }

        $transaction = $this->beginTransaction();
        try {
            $result = call_user_func($restoreCallback);
            if ($result === false) {
                $transaction->rollBack();
            } else {
                $transaction->commit();
            }
            return $result;
        } catch (\Exception $exception) {
            // PHP < 7.0
        } catch (\Throwable $exception) {
            // PHP >= 7.0
        }

        $transaction->rollBack();
        throw $exception;
    }

    /**
     * Performs restoration for soft-deleted record.
     * @return int the number of restored rows.
     * @throws InvalidConfigException on invalid configuration.
     * @throws StaleObjectException if optimistic locking is enabled and the data being updated is outdated.
     */
    protected function restoreInternal()
    {
        $restoreAttributeValues = $this->detectRestoreAttributeValues();

        $attributes = $this->owner->getDirtyAttributes();
        foreach ($restoreAttributeValues as $attribute => $value) {
            if (!is_scalar($value) && is_callable($value)) {
                $value = call_user_func($value, $this->owner);
            }
            $attributes[$attribute] = $value;
        }

        return $this->updateAttributes($attributes);
    }

    /**
     * This method is invoked before record is restored from "deleted" state.
     * The default implementation raises the {@see EVENT_BEFORE_RESTORE} event.
     * @return bool whether the record should be restored. Defaults to `true`.
     */
    public function beforeRestore()
    {
        if (method_exists($this->owner, 'beforeRestore')) {
            if (!$this->owner->beforeRestore()) {
                return false;
            }
        }

        $event = new ModelEvent();
        $this->owner->trigger(self::EVENT_BEFORE_RESTORE, $event);

        return $event->isValid;
    }

    /**
     * This method is invoked after record is restored from "deleted" state.
     * The default implementation raises the {@see EVENT_AFTER_RESTORE} event.
     * You may override this method to do postprocessing after the record is restored.
     * Make sure you call the parent implementation so that the event is raised properly.
     */
    public function afterRestore()
    {
        if (method_exists($this->owner, 'afterRestore')) {
            $this->owner->afterRestore();
        }
        $this->owner->trigger(self::EVENT_AFTER_RESTORE);
    }

    /**
     * Attempts to perform regular {@see BaseActiveRecord::delete()}, if it fails with exception, falls back to {@see softDelete()}.
     * If owner database supports transactions, regular deleting attempt will be enclosed in transaction with rollback
     * in case of failure.
     * @return false|int number of affected rows.
     * @throws \Throwable on failure.
     */
    public function safeDelete()
    {
        try {
            $transaction = $this->beginTransaction();

            $result = $this->owner->delete();
            if (isset($transaction)) {
                $transaction->commit();
            }

            return $result;
        } catch (\Exception $exception) {
            // PHP < 7.0
        } catch (\Throwable $exception) {
            // PHP >= 7.0
        }

        if (isset($transaction)) {
            $transaction->rollback();
        }

        $fallbackExceptionClass = $this->deleteFallbackException;
        if ($exception instanceof $fallbackExceptionClass) {
            return $this->softDeleteInternal();
        }

        throw $exception;
    }

    /**
     * Returns a value indicating whether the specified operation is transactional in the current owner scenario.
     * @return bool whether the specified operation is transactional in the current owner scenario.
     * @since 1.0.2
     */
    private function isTransactional($operation)
    {
        if (!$this->owner->hasMethod('isTransactional')) {
            return false;
        }

        return $this->owner->isTransactional($operation);
    }

    /**
     * Begins new database transaction if owner allows it.
     * @return \yii\db\Transaction|null transaction instance or `null` if not available.
     */
    private function beginTransaction()
    {
        $db = $this->owner->getDb();
        if ($db->hasMethod('beginTransaction')) {
            return $db->beginTransaction();
        }
        return null;
    }

    /**
     * Updates owner attributes taking {@see BaseActiveRecord::optimisticLock()} into account.
     * @param array $attributes the owner attributes (names or name-value pairs) to be updated
     * @return int the number of rows affected.
     * @throws StaleObjectException if optimistic locking is enabled and the data being updated is outdated.
     * @since 1.0.2
     */
    private function updateAttributes(array $attributes)
    {
        $owner = $this->owner;

        $lock = $owner->optimisticLock();
        if ($lock === null) {
            return $owner->updateAttributes($attributes);
        }

        $condition = $owner->getOldPrimaryKey(true);

        $attributes[$lock] = $owner->{$lock} + 1;
        $condition[$lock] = $owner->{$lock};

        $rows = $owner->updateAll($attributes, $condition);
        if (!$rows) {
            throw new StaleObjectException('The object being updated is outdated.');
        }

        foreach ($attributes as $name => $value) {
            $owner->{$name} = $value;
            $owner->setOldAttribute($name, $value);
        }

        return $rows;
    }

    /**
     * Detects values of the owner attributes, which should be applied on restoration from "deleted" state.
     * @return array values of the owner attributes in format `[attributeName => attributeValue]`
     * @throws InvalidConfigException if unable to detect restore attribute values.
     * @since 1.0.4
     */
    private function detectRestoreAttributeValues()
    {
        if ($this->restoreAttributeValues !== null) {
            return $this->restoreAttributeValues;
        }

        $restoreAttributeValues = [];
        foreach ($this->softDeleteAttributeValues as $name => $value) {
            if (is_bool($value)) {
                $restoreValue = !$value;
            } elseif (is_int($value)) {
                if ($value === 1) {
                    $restoreValue = 0;
                } elseif ($value === 0) {
                    $restoreValue = 1;
                } else {
                    $restoreValue = $value + 1;
                }
            } elseif (!is_scalar($value) && is_callable($value)) {
                $restoreValue = null;
            } else {
                throw new InvalidConfigException('Unable to automatically determine restore attribute values, "' . get_class($this) . '::$restoreAttributeValues" should be explicitly set.');
            }
            $restoreAttributeValues[$name] = $restoreValue;
        }

        return $restoreAttributeValues;
    }

    // Events :

    /**
     * {@inheritdoc}
     */
    public function events()
    {
        $events = [];

        if ($this->getReplaceRegularDelete()) {
            $events[BaseActiveRecord::EVENT_BEFORE_DELETE] = 'beforeDelete';
        }

        if ($this->getUseRestoreAttributeValuesAsDefaults()) {
            $events[BaseActiveRecord::EVENT_BEFORE_INSERT] = 'beforeInsert';
        }

        return $events;
    }

    /**
     * Handles owner 'beforeDelete' owner event, applying soft delete and preventing actual deleting.
     * @param ModelEvent $event event instance.
     */
    public function beforeDelete($event)
    {
        if (!$this->isDeleteAllowed()) {
            $this->softDeleteInternal();
            $event->isValid = false;
        }
    }

    /**
     * Handles owner 'beforeInsert' owner event, applying {@see restoreAttributeValues} to the new record.
     * @param ModelEvent $event event instance.
     * @since 1.0.4
     */
    public function beforeInsert($event)
    {
        foreach ($this->detectRestoreAttributeValues() as $attribute => $value) {
            if (isset($this->owner->{$attribute})) {
                continue;
            }

            if (!is_scalar($value) && is_callable($value)) {
                $value = call_user_func($value, $this->owner);
            }
            $this->owner->{$attribute} = $value;
        }
    }
}