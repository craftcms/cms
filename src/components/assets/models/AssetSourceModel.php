<?php
namespace Blocks;

/**
 * Asset source model class
 *
 * Used for transporting asset source data throughout the system.
 */
class AssetSourceModel extends BaseComponentModel
{
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
}
