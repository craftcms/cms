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
use craft\app\enums\SectionType;

/**
 * Class Section record.
 *
 * @var integer $id ID
 * @var integer $structureId Structure ID
 * @var string $name Name
 * @var string $handle Handle
 * @var string $type Type
 * @var boolean $hasUrls Has URLs
 * @var string $template Template
 * @var boolean $enableVersioning Enable versioning
 * @var ActiveQueryInterface $locales Locales
 * @var ActiveQueryInterface $structure Structure

 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Section extends ActiveRecord
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['handle'], 'craft\\app\\validators\\Handle', 'reservedWords' => ['id', 'dateCreated', 'dateUpdated', 'uid', 'title']],
			[['type'], 'in', 'range' => ['single', 'channel', 'structure']],
			[['name', 'handle'], 'unique'],
			[['name', 'handle', 'type'], 'required'],
			[['name', 'handle'], 'string', 'max' => 255],
			[['template'], 'string', 'max' => 500],
		];
	}

	/**
	 * @inheritdoc
	 *
	 * @return string
	 */
	public static function tableName()
	{
		return '{{%sections}}';
	}

	/**
	 * Returns the section’s locales.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getLocales()
	{
		return $this->hasMany(SectionLocale::className(), ['sectionId' => 'id']);
	}

	/**
	 * Returns the section’s structure.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getStructure()
	{
		return $this->hasOne(Structure::className(), ['id' => 'structureId']);
	}
}
