<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\records;

use craft\app\db\ActiveRecord;
use craft\app\enums\AttributeType;

/**
 * Class SectionLocale record.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class SectionLocale extends ActiveRecord
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
		return '{{%sections_i18n}}';
	}

	/**
	 * Returns the section locale’s section.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getSection()
	{
		return $this->hasOne(Section::className(), ['id' => 'sectionId']);
	}

	/**
	 * Returns the section locale’s locale.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getLocale()
	{
		return $this->hasOne(Locale::className(), ['id' => 'locale']);
	}

	/**
	 * @inheritDoc ActiveRecord::defineIndexes()
	 *
	 * @return array
	 */
	public function defineIndexes()
	{
		return [
			['columns' => ['sectionId', 'locale'], 'unique' => true],
		];
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc ActiveRecord::defineAttributes()
	 *
	 * @return array
	 */
	protected function defineAttributes()
	{
		return [
			'locale'           => [AttributeType::Locale, 'required' => true],
			'enabledByDefault' => [AttributeType::Bool, 'default' => true],
			'urlFormat'        => AttributeType::UrlFormat,
			'nestedUrlFormat'  => AttributeType::UrlFormat,
		];
	}
}
