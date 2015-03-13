<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\models;

use craft\app\enums\AttributeType;

/**
 * AssetIndexData model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class AssetIndexData extends BaseComponentModel
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
	 * @inheritDoc Model::defineAttributes()
	 *
	 * @return array
	 */
	protected function defineAttributes()
	{
		return [
			'id'		=> AttributeType::Number,
			'sourceId'	=> AttributeType::Number,
			'sessionId' => AttributeType::String,
			'offset'	=> AttributeType::Number,
			'uri'     	=> AttributeType::String,
			'size' 		=> AttributeType::Number,
			'timestamp' => AttributeType::DateTime,
			'recordId'	=> AttributeType::Number
		];
	}
}
