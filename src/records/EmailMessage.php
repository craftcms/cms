<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\records;

use yii\db\ActiveQueryInterface;
use Craft;
use craft\app\db\ActiveRecord;

Craft::$app->requireEdition(Craft::Client);

/**
 * Class EmailMessage record.
 *
 * @property integer $id ID
 * @property ActiveQueryInterface $locale Locale
 * @property string $key Key
 * @property string $subject Subject
 * @property string $body Body
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class EmailMessage extends ActiveRecord
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
			[['key'], 'unique', 'targetAttribute' => ['key', 'locale']],
			[['key', 'locale', 'subject', 'body'], 'required'],
			[['key'], 'string', 'max' => 150],
			[['subject'], 'string', 'max' => 1000],
		];
	}

	/**
	 * @inheritdoc
	 *
	 * @return string
	 */
	public static function tableName()
	{
		return '{{%emailmessages}}';
	}

	/**
	 * Returns the email message’s locale.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getLocale()
	{
		return $this->hasOne(Locale::className(), ['id' => 'locale']);
	}
}
