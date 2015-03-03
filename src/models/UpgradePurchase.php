<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\models;

use craft\app\base\Model;
use craft\app\enums\AttributeType;

/**
 * Used to hold edition upgrade purchase order data.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class UpgradePurchase extends Model
{
	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc Model::defineAttributes()
	 *
	 * @return array
	 */
	protected function defineAttributes()
	{
		return [
			'ccTokenId'     => [AttributeType::String, 'required' => true],
			'edition'       => [AttributeType::Number, 'required' => true],
			'expectedPrice' => [AttributeType::Number, 'required' => true],
			'success'       => AttributeType::Bool,
		];
	}
}
