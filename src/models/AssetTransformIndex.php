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
 * Class AssetTransformIndex model.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class AssetTransformIndex extends Model
{
	// Public Methods
	// =========================================================================

	/**
	 * Use the folder name as the string representation.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->id;
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
			'id'             => AttributeType::Number,
			'fileId'         => AttributeType::Number,
			'filename'       => AttributeType::Number,
			'format'         => AttributeType::Number,
			'location'       => AttributeType::String,
			'sourceId'       => AttributeType::Number,
			'fileExists'     => AttributeType::Bool,
			'inProgress'     => AttributeType::Bool,
			'dateIndexed'    => AttributeType::DateTime,
			'dateUpdated'    => AttributeType::DateTime,
			'dateCreated'    => AttributeType::DateTime,

			// Format detected for auto transform
			'detectedFormat' => AttributeType::Number,

			// The AssetTransformModel that defines the transformation to make
			'transform'      => AttributeType::Mixed
		];
	}
}
