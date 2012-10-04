<?php
namespace Blocks;

/**
 * Asset block model class
 *
 * Used for transporting asset block data throughout the system.
 */
class AssetBlockModel extends BaseBlockModel
{
	/**
	 * Returns the type of entity these blocks will be attached to.
	 *
	 * @return string
	 */
	public function getEntityType()
	{
		return 'asset';
	}
}
