<?php
namespace Craft;

/**
 * Used to hold edition upgrade information.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
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
			'countries'       => AttributeType::Mixed,
			'states'          => AttributeType::Mixed,
		);
	}
}
