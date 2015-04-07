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
 * Class CategoryGroupLocale record.
 *
 * @property integer $id ID
 * @property integer $groupId Group ID
 * @property ActiveQueryInterface $locale Locale
 * @property string $urlFormat URL format
 * @property string $nestedUrlFormat Nested URL format
 * @property ActiveQueryInterface $group Group
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class CategoryGroupLocale extends ActiveRecord
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
			[['groupId'], 'unique', 'targetAttribute' => ['groupId', 'locale']],
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
		return '{{%categorygroups_i18n}}';
	}

	/**
	 * Returns the category group locale’s group.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getGroup()
	{
		return $this->hasOne(CategoryGroup::className(), ['id' => 'groupId']);
	}

	/**
	 * Returns the category group locale’s locale.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getLocale()
	{
		return $this->hasOne(Locale::className(), ['id' => 'locale']);
	}
}
