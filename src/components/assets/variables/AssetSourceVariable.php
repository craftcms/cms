<?php
namespace Blocks;

/**
 *
 */
class AssetSourceVariable extends BaseModelVariable
{
	/**
	 * Returns a source type variable based on this source model.
	 *
	 * @return AssetSourceTypeVariable|null
	 */
	public function sourceType()
	{
		$sourceType = blx()->assetSources->populateSourceType($this->model);
		if ($sourceType)
		{
			return new AssetSourceTypeVariable($sourceType);
		}
	}
}
