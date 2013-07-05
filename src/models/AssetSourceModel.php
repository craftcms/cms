<?php
namespace Craft;

/**
 * Asset source model class
 *
 * Used for transporting asset source data throughout the system.
 */
class AssetSourceModel extends BaseComponentModel
{
	/**
	 * Use the translated source name as the string representation.
	 *
	 * @return string
	 */
	function __toString()
	{
		return Craft::t($this->name);
	}

	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		$attributes = parent::defineAttributes();

		$attributes['name'] = AttributeType::String;
		$attributes['type']['default'] = 'Local';
		$attributes['sortOrder'] = AttributeType::String;

		return $attributes;
	}

	/**
	 * Return the SourceType's name.
	 *
	 * @return string
	 */
	public function getSourceTypeName()
	{
		$sourceType =  craft()->assetSources->populateSourceType($this);
		if ($sourceType)
		{
			return $sourceType->getName();
		}
		return "";
	}

}
