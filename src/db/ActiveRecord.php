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
 * @since 3.0
 */
abstract class ActiveRecord extends \yii\db\ActiveRecord
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        $this->prepareForDb();
        return parent::beforeSave($insert);
    }

    /**
     * Prepares record values for DB storage.
     */
    protected function prepareForDb()
    {
        foreach ($this->fields() as $attribute) {
            $this->$attribute = Db::prepareValueForDb($this->$attribute);
        }

        // Prepare the values
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
