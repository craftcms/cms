<?php
namespace Craft;

/**
 * Used to hold package purchase order data.
 */
class PackagePurchaseOrderModel extends BaseModel
{
	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'ccTokenId'     => array(AttributeType::String, 'required' => true),
			'package'       => array(AttributeType::String, 'required' => true),
			'expectedPrice' => array(AttributeType::Number, 'required' => true),
			'success'       => AttributeType::Bool,
		);
	}
}
