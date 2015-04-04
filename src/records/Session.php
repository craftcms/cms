<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\records;

use yii\db\ActiveQueryInterface;
use craft\app\db\ActiveRecord;

/**
 * Class Session record.
 *
 * @property integer $id ID
 * @property integer $userId User ID
 * @property string $token Token
 * @property ActiveQueryInterface $user User
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Session extends ActiveRecord
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['token'], 'required'],
			[['token'], 'string', 'max' => 100],
		];
	}

	/**
	 * @inheritdoc
	 *
	 * @return string
	 */
	public static function tableName()
	{
		return '{{%sessions}}';
	}

	/**
	 * Returns the session’s user.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getUser()
	{
		return $this->hasOne(User::className(), ['id' => 'userId']);
	}
}
