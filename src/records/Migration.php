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
 * Class Migration record.
 *
 * @var integer $id ID
 * @var integer $pluginId Plugin ID
 * @var string $version Version
 * @var \DateTime $applyTime Apply time
 * @var ActiveQueryInterface $plugin Plugin

 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Migration extends ActiveRecord
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['applyTime'], 'craft\\app\\validators\\DateTime'],
			[['version'], 'unique'],
			[['version', 'applyTime'], 'required'],
			[['version'], 'string', 'max' => 255],
		];
	}

	/**
	 * @inheritdoc
	 *
	 * @return string
	 */
	public static function tableName()
	{
		return '{{%migrations}}';
	}

	/**
	 * Returns the migration’s plugin.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getPlugin()
	{
		return $this->hasOne(Plugin::className(), ['id' => 'pluginId']);
	}
}
