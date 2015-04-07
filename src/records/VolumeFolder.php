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
 * Class VolumeFolder record.
 *
 * @property integer $id ID
 * @property integer $parentId Parent ID
 * @property integer $volumeId Volume ID
 * @property string $name Name
 * @property string $path Path
 * @property ActiveQueryInterface $parent Parent
 * @property ActiveQueryInterface $volume Volume
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class VolumeFolder extends ActiveRecord
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['name'], 'unique', 'targetAttribute' => ['name', 'parentId', 'volumeId']],
			[['name'], 'required'],
		];
	}

	/**
	 * @inheritdoc
	 *
	 * @return string
	 */
	public static function tableName()
	{
		return '{{%volumefolders}}';
	}

	/**
	 * Returns the asset folder’s parent.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getParent()
	{
		return $this->hasOne(VolumeFolder::className(), ['id' => 'parentId']);
	}

	/**
	 * Returns the asset folder’s source.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getVolume()
	{
		return $this->hasOne(Volume::className(), ['id' => 'volumeId']);
	}
}
