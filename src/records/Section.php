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
 * Class Section record.
 *
 * @property integer $id ID
 * @property integer $structureId Structure ID
 * @property string $name Name
 * @property string $handle Handle
 * @property string $type Type
 * @property boolean $hasUrls Has URLs
 * @property string $template Template
 * @property boolean $enableVersioning Enable versioning
 * @property ActiveQueryInterface $locales Locales
 * @property ActiveQueryInterface $structure Structure
 *
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
