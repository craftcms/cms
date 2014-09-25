<?php
namespace Craft;

/**
 * Asset source model class.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.models
 * @since     1.0
 */
class AssetIndexDataModel extends BaseComponentModel
{
	// Public Methods
	// =========================================================================

	/**
	 * Use the translated source name as the string representation.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->uri;
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
			'id'		=> AttributeType::Number,
			'sourceId'	=> AttributeType::Number,
			'sessionId' => AttributeType::String,
			'offset'	=> AttributeType::Number,
			'uri'     	=> AttributeType::String,
			'size' 		=> AttributeType::Number,
			'recordId'	=> AttributeType::Number
		);
	}
}
