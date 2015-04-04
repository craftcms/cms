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
 * Class Plugin record.
 *
 * @property integer $id ID
 * @property string $class Class
 * @property string $version Version
 * @property boolean $enabled Enabled
 * @property array $settings Settings
 * @property \DateTime $installDate Install date
 * @property ActiveQueryInterface $migrations Migrations
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Plugin extends ActiveRecord
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['installDate'], 'craft\\app\\validators\\DateTime'],
			[['class', 'version', 'installDate'], 'required'],
			[['class'], 'string', 'max' => 150],
			[['version'], 'string', 'max' => 15],
		];
	}

	/**
	 * @inheritdoc
	 *
	 * @return string
	 */
	public static function tableName()
	{
		return '{{%plugins}}';
	}

	/**
	 * Returns the plugin’s migrations.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getMigrations()
	{
		return $this->hasMany(Migration::className(), ['pluginId' => 'id']);
	}
}
