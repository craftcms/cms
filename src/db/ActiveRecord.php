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

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        foreach ($this->fields() as $attribute) {
            $this->$attribute = Db::prepareValueForDb($this->$attribute);
        }

        if ($this->getIsNewRecord()) {
            // Prepare the values
            $now = Db::prepareDateForDb(new \DateTime());

            if ($this->hasAttribute('dateCreated')) {
                $this->dateCreated = $now;
            }

            if ($this->hasAttribute('dateUpdated')) {
                $this->dateUpdated = $now;
            }

            if ($this->hasAttribute('uid')) {
                $this->uid = StringHelper::UUID();
            }
        }

        return parent::beforeSave($insert);
    }
}
