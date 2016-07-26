<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\db;

use craft\app\helpers\Db;
use craft\app\helpers\StringHelper;

/**
 * Active Record base class.
 *
 * @property string $dateCreated Date created
 * @property string $dateUpdated Date updated
 * @property string $uid         UUID
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
abstract class ActiveRecord extends \yii\db\ActiveRecord
{
    // Static
    // =========================================================================

    /**
     * @inheritdoc
     *
     * @return string[]
     */
    public static function primaryKey()
    {
        return ['id'];
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        // Prepare the values
        $now = Db::prepareDateForDb(new \DateTime());

        foreach ($this->attributes() as $attribute) {
            if ($attribute === 'dateCreated' && $this->getIsNewRecord()) {
                $this->dateCreated = $now;
            } else if ($attribute === 'dateUpdated') {
                $this->dateUpdated = $now;
            } else if ($attribute === 'uid' && $this->getIsNewRecord()) {
                $this->uid = StringHelper::UUID();
            } else {
                $this->$attribute = Db::prepareValueForDb($this->$attribute);
            }
        }

        return parent::beforeSave($insert);
    }
}
