<?php
namespace Blocks;

/**
 * Asset source model class
 *
 * Used for transporting asset source data throughout the system.
 */
class AssetSourceModel extends BaseComponentModel
{
	private $_blockType;

	public function defineAttributes()
	{
		$attributes = parent::defineAttributes();

		$attributes['name'] = AttributeType::String;
		$attributes['type']['default'] = 'Local';

		return $attributes;
	}

	/**
	 * Saves the asset block.
	 *
	 * @return bool
	 */
	public function save()
	{
		return blx()->assetSources->saveSource($this);
	}

	/**
	 * Returns the source type.
	 *
	 * @return BaseAssetSource|null
	 */
	public function getSourceType()
	{
		if (!isset($this->_sourceType))
		{
			$this->_sourceType = blx()->assetSources->populateSourceType($this);
		}
		return $this->_sourceType;
	}
}
