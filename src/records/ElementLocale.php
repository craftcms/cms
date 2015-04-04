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
 * Element locale data record class.
 *
 * @property integer $id ID
 * @property integer $elementId Element ID
 * @property ActiveQueryInterface $locale Locale
 * @property string $slug Slug
 * @property string $uri URI
 * @property boolean $enabled Enabled
 * @property ActiveQueryInterface $element Element
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class ElementLocale extends ActiveRecord
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
			[['elementId'], 'unique', 'targetAttribute' => ['elementId', 'locale']],
			[['uri'], 'unique', 'targetAttribute' => ['uri', 'locale']],
			[['locale'], 'required'],
			[['uri'], 'craft\\app\\validators\\Uri'],
		];
	}

	/**
	 * @inheritdoc
	 *
	 * @return string
	 */
	public static function tableName()
	{
		return '{{%elements_i18n}}';
	}

	/**
	 * Returns the element locale’s element.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getElement()
	{
		return $this->hasOne(Element::className(), ['id' => 'elementId']);
	}

	/**
	 * Returns the element locale’s locale.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getLocale()
	{
		return $this->hasOne(Locale::className(), ['id' => 'locale']);
	}
}
