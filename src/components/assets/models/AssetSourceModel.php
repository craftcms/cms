<?php
namespace Blocks;

/**
 * Asset source model class
 *
 * Used for transporting asset source data throughout the system.
 */
class AssetSourceModel extends BaseComponentModel
{
	/**
	 * @return mixed
	 */
	public function defineAttributes()
	{
		$attributes = parent::defineAttributes();

		$attributes['name'] = AttributeType::String;
		$attributes['type']['default'] = 'Local';

		return $attributes;
	}
}
