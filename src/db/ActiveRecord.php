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

        // Prepare the values
        $now = Db::prepareDateForDb(new \DateTime());

        if ($this->getIsNewRecord()) {
            if ($this->hasAttribute('dateCreated')) {
                $this->dateCreated = $now;
            }

            if ($this->hasAttribute('uid')) {
                $this->uid = StringHelper::UUID();
            }
        }

        if ($this->hasAttribute('dateUpdated')) {
            $this->dateUpdated = $now;
        }

        return parent::beforeSave($insert);
    }
}
