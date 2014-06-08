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
			'ccTokenId'           => array(AttributeType::String, 'required' => true),
			'edition'             => array(AttributeType::Number, 'required' => true),
			'expectedPrice'       => array(AttributeType::Number, 'required' => true),
			'success'             => AttributeType::Bool,
			'name'                => AttributeType::String,
			'companyName'         => AttributeType::String,
			'addressLine1'        => AttributeType::String,
			'addressLine2'        => AttributeType::String,
			'addressLine3'        => AttributeType::String,
			'country'             => AttributeType::String,
			'stateProvinceRegion' => AttributeType::String,
			'cityTown'            => AttributeType::String,
			'zipPostalCode'       => AttributeType::String,
		);
	}
}
