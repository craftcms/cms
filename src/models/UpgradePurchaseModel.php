<?php
namespace Craft;

/**
 * Used to hold edition upgrade purchase order data.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.models
 * @since     2.0
 */
class UpgradePurchaseModel extends BaseModel
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseModel::rules()
	 *
	 * @return array
	 */
	public function rules()
	{
		$rules = parent::rules();

		if ($this->expectedPrice == 0)
		{
			// CC info not required
			foreach ($rules as &$rule)
			{
				if ($rule[1] == 'required')
				{
					$attributes = explode(',', $rule[0]);

					foreach (array('ccTokenId', 'expMonth', 'expYear') as $attribute)
					{
						$pos = array_search($attribute, $attributes);

						if ($pos !== false)
						{
							array_splice($attributes, $pos, 1);
						}
					}

					$rule[0] = implode(',', $attributes);
					break;
				}
			}
		}

		return $rules;
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseModel::defineAttributes()
	 *
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'ccTokenId'        => array(AttributeType::String, 'required' => true),
			'expMonth'         => array(AttributeType::Number, 'required' => true),
			'expYear'          => array(AttributeType::Number, 'required' => true),
			'edition'          => array(AttributeType::Number, 'required' => true),
			'expectedPrice'    => array(AttributeType::Number, 'decimals' => 4, 'required' => true),
			'name'             => array(AttributeType::String, 'required' => true),
			'email'            => array(AttributeType::Email, 'required' => true),
			'businessName'     => AttributeType::String,
			'businessAddress1' => AttributeType::String,
			'businessAddress2' => AttributeType::String,
			'businessCity'     => AttributeType::String,
			'businessState'    => AttributeType::String,
			'businessCountry'  => AttributeType::String,
			'businessZip'      => AttributeType::String,
			'businessTaxId'    => AttributeType::String,
			'purchaseNotes'    => AttributeType::String,
			'couponCode'       => AttributeType::String,
			'success'          => AttributeType::Bool,
		);
	}
}
