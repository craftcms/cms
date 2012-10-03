<?php
namespace Blocks;

/**
 * Assets functions
 */
class AssetsVariable
{
	// -------------------------------------------
	//  Sources
	// -------------------------------------------

	/**
	 * Returns all installed asset source types.
	 *
	 * @return array
	 */
	public function getAllSourceTypes()
	{
		$sourceTypes = blx()->assetSources->getAllSourceTypes();
		return AssetSourceTypeVariable::populateVariables($sourceTypes);
	}

	/**
	 * Gets an asset source type.
	 *
	 * @param string $class
	 * @return AssetSourceTypeVariable|null
	 */
	public function getSourceType($class)
	{
		$sourceType = blx()->assetSources->getSourceType($class);

		if ($sourceType)
		{
			return new AssetSourceTypeVariable($sourceType);
		}
	}

	/**
	 * Returns all asset sources.
	 *
	 * @return array
	 */
	public function sources()
	{
		$sources = blx()->assetSources->getAllSources();
		return AssetSourceVariable::populateVariables($sources);
	}

	/**
	 * Gets an asset source by its ID.
	 *
	 * @param int $id
	 * @return AssetSourceModel|null
	 */
	public function getSourceById($id)
	{
		$source = blx()->assetSources->getSourceById($id);

		if ($source)
		{
			return new AssetSourceVariable($source);
		}
	}

	// -------------------------------------------
	//  Blocks
	// -------------------------------------------

	/**
	 * Returns all asset blocks.
	 *
	 * @return array
	 */
	public function assetBlocks()
	{
		$blocks = blx()->assetBlocks->getAllBlocks();
		return BlockVariable::populateVariables($blocks);
	}

	/**
	 * Gets an asset block by its ID.
	 *
	 * @param int $id
	 * @return BlockVariable|null
	 */
	public function getAssetBlockById($id)
	{
		$block = blx()->assetBlocks->getBlockById($id);

		if ($block)
		{
			return new BlockVariable($block);
		}
	}
}
