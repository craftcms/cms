<?php
namespace Blocks;

/**
 * Asset source package class
 *
 * Used for transporting asset source data throughout the system.
 */
class AssetSourcePackage extends BaseComponentPackage
{
	public $name;

	/**
	 * Saves the entry block.
	 *
	 * @return bool
	 */
	public function save()
	{
		return blx()->assetSources->saveSource($this);
	}
}
