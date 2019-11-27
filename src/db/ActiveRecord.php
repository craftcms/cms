<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\db;

use craft\helpers\Db;
use craft\helpers\StringHelper;

/**
 * Active Record base class.
 *
 * @property string $dateCreated Date created
 * @property string $dateUpdated Date updated
 * @property string $uid UUID
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
abstract class ActiveRecord extends \yii\db\ActiveRecord
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     * @since 3.4.0
     */
    public function __set($name, $value)
    {
        if ($this->hasAttribute($name)) {
            $value = Db::prepareValueForDb($value);
        }
        parent::__set($name, $value);
    }

    /**
     * @inheritdoc
     * @since 3.4.0
     */
    public function setAttribute($name, $value)
    {
        $value = Db::prepareValueForDb($value);
        parent::setAttribute($name, $value);
    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        $this->prepareForDb();
        return parent::beforeSave($insert);
    }

    /**
     * Sets the `dateCreated`, `dateUpdated`, and `uid` attributes on the record.
     *
     * @since 3.1.0
     */
    protected function prepareForDb()
    {
        $now = Db::prepareDateForDb(new \DateTime());

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
        } else if (
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
}
