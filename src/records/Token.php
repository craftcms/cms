<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\records;

use craft\app\db\ActiveRecord;

/**
 * Token record.
 *
 * @property integer $id ID
 * @property string $token Token
 * @property array $route Route
 * @property integer $usageLimit Usage limit
 * @property integer $usageCount Usage count
 * @property \DateTime $expiryDate Expiry date
 *
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
