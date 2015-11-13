<?php
namespace Craft;

/**
 * Used to hold edition upgrade information.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.models
 * @since     2.4
 */
class UpgradeInfoModel extends BaseModel
{
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
			'editions'        => array(AttributeType::Mixed, 'required' => true),
			'stripePublicKey' => array(AttributeType::String, 'required' => true),
		);
	}
}
