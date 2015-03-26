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
use craft\app\enums\ColumnType;

/**
 * Class Plugin record.
 *
 * @var integer $id ID
 * @var string $class Class
 * @var string $version Version
 * @var boolean $enabled Enabled
 * @var array $settings Settings
 * @var \DateTime $installDate Install date
 * @var ActiveQueryInterface $migrations Migrations

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
