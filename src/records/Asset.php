<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\records;

use yii\db\ActiveQueryInterface;
use craft\app\enums\AttributeType;
use craft\app\enums\ColumnType;
use craft\app\db\ActiveRecord;

/**
 * Class Asset record.
 *
 * @todo Create save function which calls parent::save and then updates the meta data table (keywords, author, etc)
 *
 * @var integer $id ID
 * @var integer $sourceId Source ID
 * @var integer $folderId Folder ID
 * @var string $filename Filename
 * @var string $kind Kind
 * @var integer $width Width
 * @var integer $height Height
 * @var integer $size Size
 * @var \DateTime $dateModified Date modified
 * @var ActiveQueryInterface $element Element
 * @var ActiveQueryInterface $source Source
 * @var ActiveQueryInterface $folder Folder

 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Asset extends ActiveRecord
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['width'], 'number', 'min' => 0, 'max' => 65535, 'integerOnly' => true],
			[['height'], 'number', 'min' => 0, 'max' => 65535, 'integerOnly' => true],
			[['size'], 'number', 'min' => 0, 'max' => 4294967295, 'integerOnly' => true],
			[['dateModified'], 'craft\\app\\validators\\DateTime'],
			[['filename'], 'unique', 'targetAttribute' => ['filename', 'folderId']],
			[['filename', 'kind'], 'required'],
			[['kind'], 'string', 'max' => 50],
		];
	}

	/**
	 * @inheritdoc
	 *
	 * @return string
	 */
	public static function tableName()
	{
		return '{{%assets}}';
	}

	/**
	 * Returns the asset file’s element.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getElement()
	{
		return $this->hasOne(Element::className(), ['id' => 'id']);
	}

	/**
	 * Returns the asset file’s source.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getSource()
	{
		return $this->hasOne(AssetSource::className(), ['id' => 'sourceId']);
	}

	/**
	 * Returns the asset file’s folder.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getFolder()
	{
		return $this->hasOne(AssetFolder::className(), ['id' => 'folderId']);
	}
}
