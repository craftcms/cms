<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\records;

use craft\app\db\ActiveRecord;
use craft\app\enums\AttributeType;
use craft\app\enums\ColumnType;

/**
 * Token record.
 *
 * @var integer $id ID
 * @var string $token Token
 * @var array $route Route
 * @var integer $usageLimit Usage limit
 * @var integer $usageCount Usage count
 * @var \DateTime $expiryDate Expiry date

 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Token extends ActiveRecord
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['usageLimit'], 'number', 'min' => 0, 'max' => 255, 'integerOnly' => true],
			[['usageCount'], 'number', 'min' => 0, 'max' => 255, 'integerOnly' => true],
			[['expiryDate'], 'craft\\app\\validators\\DateTime'],
			[['token'], 'unique'],
			[['token', 'expiryDate'], 'required'],
			[['token'], 'string', 'length' => 32],
		];
	}

	/**
	 * @inheritdoc
	 *
	 * @return string
	 */
	public static function tableName()
	{
		return '{{%tokens}}';
	}
}
