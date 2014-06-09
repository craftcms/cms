<?php
namespace Craft;

/**
 * Used to hold edition upgrade purchase order data.
 */
class UpgradePurchaseModel extends BaseModel
{
	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'ccTokenId'     => array(AttributeType::String, 'required' => true),
			'edition'       => array(AttributeType::Number, 'required' => true),
			'expectedPrice' => array(AttributeType::Number, 'required' => true),
			'success'       => AttributeType::Bool,
		);
	}
}
