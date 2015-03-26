<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\records;

use yii\db\ActiveQueryInterface;
use craft\app\db\ActiveRecord;
use craft\app\enums\AttributeType;

/**
 * Class AssetFolder record.
 *
 * @var integer $id ID
 * @var integer $parentId Parent ID
 * @var integer $sourceId Source ID
 * @var string $name Name
 * @var string $path Path
 * @var ActiveQueryInterface $parent Parent
 * @var ActiveQueryInterface $source Source

 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class AssetFolder extends ActiveRecord
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['name'], 'unique', 'targetAttribute' => ['name', 'parentId', 'sourceId']],
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
		return '{{%assetfolders}}';
	}

	/**
	 * Returns the asset folder’s parent.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getParent()
	{
		return $this->hasOne(AssetFolder::className(), ['id' => 'parentId']);
	}

	/**
	 * Returns the asset folder’s source.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getSource()
	{
		return $this->hasOne(AssetSource::className(), ['id' => 'sourceId']);
	}
}
