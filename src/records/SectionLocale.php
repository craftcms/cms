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
 * Class SectionLocale record.
 *
 * @var integer $id ID
 * @var integer $sectionId Section ID
 * @var ActiveQueryInterface $locale Locale
 * @var boolean $enabledByDefault Enabled by default
 * @var string $urlFormat URL format
 * @var string $nestedUrlFormat Nested URL format
 * @var ActiveQueryInterface $section Section

 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class SectionLocale extends ActiveRecord
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['locale'], 'craft\\app\\validators\\Locale'],
			[['sectionId'], 'unique', 'targetAttribute' => ['sectionId', 'locale']],
			[['locale'], 'required'],
			[['urlFormat', 'nestedUrlFormat'], 'craft\\app\\validators\\UrlFormat'],
		];
	}

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
}
