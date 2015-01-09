<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\models;

use Craft;
use craft\app\enums\AttributeType;

/**
 * The AssetTransform model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class AssetTransform extends BaseModel
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
		return $this->name;
	}

	/**
	 * Return true if the transform is a named one.
	 *
	 * @return bool
	 */
	public function isNamedTransform()
	{
		return (bool) $this->getAttribute('name');
	}

	/**
	 * Get a list of transform modes.
	 *
	 * @return array
	 */
	public static function getTransformModes()
	{
		return [
			'crop'    => Craft::t('Scale and crop'),
			'fit'     => Craft::t('Scale to fit'),
			'stretch' => Craft::t('Stretch to fit')
		];
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
		return [
			'id'                  => AttributeType::Number,
			'name'                => AttributeType::String,
			'handle'              => AttributeType::Handle,
			'width'               => AttributeType::Number,
			'height'              => AttributeType::Number,
			'format'              => AttributeType::String,
			'dimensionChangeTime' => AttributeType::DateTime,
			'mode'                => [AttributeType::String, 'default' => 'crop'],
			'position'            => [AttributeType::String, 'default' => 'center-center'],
			'quality'             => [AttributeType::Number],
		];
	}
}
