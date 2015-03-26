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
 * Class Route record.
 *
 * @var integer $id ID
 * @var ActiveQueryInterface $locale Locale
 * @var string $urlParts URL parts
 * @var string $urlPattern URL pattern
 * @var string $template Template
 * @var string $sortOrder Sort order

 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Route extends ActiveRecord
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
			[['urlPattern'], 'unique'],
			[['urlParts', 'urlPattern', 'template'], 'required'],
		];
	}

	/**
	 * @inheritdoc
	 *
	 * @return string
	 */
	public static function tableName()
	{
		return '{{%routes}}';
	}

	/**
	 * Returns the route’s locale.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getLocale()
	{
		return $this->hasOne(Locale::className(), ['id' => 'locale']);
	}
}
