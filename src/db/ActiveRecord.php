<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\db;

use Craft;
use craft\events\DefineBehaviorsEvent;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use DateTime;

/**
 * Active Record base class.
 *
 * @property DateTime|string|null $dateCreated Date created
 * @property DateTime|string|null $dateUpdated Date updated
 * @property string $uid UUID
 * @method ActiveQuery hasMany(string $class, array $link) See [[\yii\db\BaseActiveRecord::hasMany()]] for more info.
 * @method ActiveQuery hasOne(string $class, array $link) See [[\yii\db\BaseActiveRecord::hasOne()]] for more info.
 * @method ActiveQuery findBySql(string $sql, array $params) See [[\yii\db\ActiveRecord::findBySql()]] for more info.
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
abstract class ActiveRecord extends \yii\db\ActiveRecord
{
    /**
     * @event DefineBehaviorsEvent The event that is triggered when defining the class behaviors
     * @see behaviors()
     * @since 3.4.0
     */
    public const EVENT_DEFINE_BEHAVIORS = 'defineBehaviors';

    /**
     * @inheritdoc
     * @return ActiveQuery the newly created [[ActiveQuery]] instance.
     */
    public static function find(): ActiveQuery
    {
        return Craft::createObject(ActiveQuery::class, [static::class]);
    }

    /**
     * @inheritdoc
     * @since 3.4.0
     */
    public function __set($name, $value)
    {
        if ($this->hasAttribute($name)) {
            $value = $this->_prepareValue($name, $value);
        }
        parent::__set($name, $value);
    }

    /**
     * @inheritdoc
     * @since 3.4.0
     */
    public function behaviors(): array
    {
        // Fire a 'defineBehaviors' event
        $event = new DefineBehaviorsEvent();
        $this->trigger(self::EVENT_DEFINE_BEHAVIORS, $event);
        return $event->behaviors;
    }

    /**
     * @inheritdoc
     * @since 3.4.0
     */
    public function setAttribute($name, $value): void
    {
        $value = $this->_prepareValue($name, $value);
        parent::setAttribute($name, $value);
    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert): bool
    {
        $this->prepareForDb();
        return parent::beforeSave($insert);
    }

    /**
     * Sets the `dateCreated`, `dateUpdated`, and `uid` attributes on the record.
     *
     * @since 3.1.0
     */
    protected function prepareForDb(): void
    {
        $now = Db::prepareDateForDb(DateTimeHelper::now());

        if ($this->getIsNewRecord()) {
            if ($this->hasAttribute('dateCreated') && !isset($this->dateCreated)) {
                $this->dateCreated = $now;
            }

            if ($this->hasAttribute('dateUpdated') && !isset($this->dateUpdated)) {
                $this->dateUpdated = $now;
            }

            if ($this->hasAttribute('uid') && !isset($this->uid)) {
                $this->uid = StringHelper::UUID();
            }

            // Unset any empty primary key values
            foreach (static::primaryKey() as $key) {
                if ($this->hasAttribute($key) && empty($this->$key)) {
                    unset($this->$key);
                }
            }
        } elseif (
            !empty($this->getDirtyAttributes()) &&
            $this->hasAttribute('dateUpdated')
        ) {
            if (!$this->isAttributeChanged('dateUpdated')) {
                $this->dateUpdated = $now;
            } else {
                $this->markAttributeDirty('dateUpdated');
            }
        }
    }

    /**
     * Prepares a value to be saved to the database.
     *
     * @param string $name The attribute name
     * @param mixed $value The attribute value
     * @return mixed The prepared value
     * @since 3.4.0
     */
    private function _prepareValue(string $name, mixed $value): mixed
    {
        $columnType = static::getTableSchema()->columns[$name]->dbType ?? null;
        return Db::prepareValueForDb($value, $columnType);
    }
}
