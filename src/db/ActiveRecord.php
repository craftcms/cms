<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\db;

use craft\app\helpers\DateTimeHelper;
use craft\app\helpers\DbHelper;
use craft\app\helpers\StringHelper;

/**
 * Active Record base class.
 *
 * @property string $dateCreated Date created
 * @property string $dateUpdated Date updated
 * @property string $uid UUID
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
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
		foreach ($this->attributes() as $attribute)
		{
			if ($attribute === 'dateCreated' && $this->getIsNewRecord())
			{
				$this->dateCreated = DateTimeHelper::currentTimeForDb();
			}
			else if ($attribute === 'dateUpdated')
			{
				$this->dateUpdated = DateTimeHelper::currentTimeForDb();
			}
			else if ($attribute === 'uid' && $this->getIsNewRecord())
			{
				$this->uid = StringHelper::UUID();
			}
			else
			{
				$this->$attribute = DbHelper::prepValue($this->$attribute);
			}
		}

		return parent::beforeSave($insert);
	}
}
