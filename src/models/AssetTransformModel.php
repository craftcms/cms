<?php
namespace Craft;

/**
 * Class AssetTransformModel
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.models
 * @since     1.0
 */
class AssetTransformModel extends BaseModel
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
		return (string)$this->name;
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
		return array(
			'crop'    => Craft::t('Scale and crop'),
			'fit'     => Craft::t('Scale to fit'),
			'stretch' => Craft::t('Stretch to fit')
		);
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
			'id'                  => AttributeType::Number,
			'name'                => AttributeType::String,
			'handle'              => AttributeType::Handle,
			'width'               => AttributeType::Number,
			'height'              => AttributeType::Number,
			'format'              => AttributeType::String,
			'dimensionChangeTime' => AttributeType::DateTime,
			'mode'                => array(AttributeType::String, 'default' => 'crop'),
			'position'            => array(AttributeType::String, 'default' => 'center-center'),
			'quality'             => array(AttributeType::Number),
		);
	}
}
