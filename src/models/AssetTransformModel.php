<?php
namespace Craft;

/**
 * Class AssetTransformModel
 *
 * @package craft.app.models
 */
class AssetTransformModel extends BaseModel
{
	/**
	 * Use the folder name as the string representation.
	 *
	 * @return string
	 */
	function __toString()
	{
		return $this->name;
	}

	/**
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
			'dimensionChangeTime' => AttributeType::DateTime,
			'mode'                => array(AttributeType::String, 'default' => 'crop'),
			'position'            => array(AttributeType::String, 'default' => 'center-center'),
			'quality'             => array(AttributeType::Number),
		);
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
}
