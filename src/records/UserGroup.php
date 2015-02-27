<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\records;

use Craft;
use craft\app\enums\AttributeType;

Craft::$app->requireEdition(Craft::Pro);

/**
 * Class UserGroup record.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class UserGroup extends BaseRecord
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 *
	 * @return string
	 */
	public static function tableName()
	{
		return '{{%usergroups}}';
	}

	/**
	 * Returns the group’s users.
	 *
	 * @return \yii\db\ActiveQueryInterface
	 */
	public function getUsers()
	{
		return $this->hasMany(User::className(), ['id' => 'userId'])
			->viaTable('{{%usergroups_users}}', ['groupId' => 'id']);
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseRecord::defineAttributes()
	 *
	 * @return array
	 */
	protected function defineAttributes()
	{
		return [
			'name'   => [AttributeType::Name, 'required' => true],
			'handle' => [AttributeType::Handle, 'required' => true],
		];
	}
}
