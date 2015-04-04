<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\models;

use craft\app\base\Model;

/**
 * Used to hold edition upgrade purchase order data.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class UpgradePurchase extends Model
{
	// Properties
	// =========================================================================

	/**
	 * @var string Cc token ID
	 */
	public $ccTokenId;

	/**
	 * @var integer Edition
	 */
	public $edition;

	/**
	 * @var integer Expected price
	 */
	public $expectedPrice;

	/**
	 * @var boolean Success
	 */
	public $success = false;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['edition'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['expectedPrice'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['ccTokenId', 'edition', 'expectedPrice'], 'required'],
			[['ccTokenId', 'edition', 'expectedPrice', 'success'], 'safe', 'on' => 'search'],
		];
	}
}
