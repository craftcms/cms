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
	 * Populates an asset source type.
	 *
	 * @param AssetSourceModel $source
	 * @return AssetSourceTypeVariable|null
	 */
	public function populateSourceType(AssetSourceModel $source)
	{
		$sourceType = blx()->assetSources->populateSourceType($source);
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
		return blx()->assetSources->getAllSources();
	}

	/**
	 * Gets an asset source by its ID.
	 *
	 * @param int $id
	 * @return AssetSourceModel|null
	 */
	public function getSourceById($id)
	{
		return blx()->assetSources->getSourceById($id);
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
		return blx()->assetBlocks->getAllBlocks();
	}

	/**
	 * Gets an asset block by its ID.
	 *
	 * @param int $id
	 * @return AssetBlockModel|null
	 */
	public function getAssetBlockById($id)
	{
		return blx()->assetBlocks->getBlockById($id);
	}

	// -------------------------------------------
	//  Files
	// -------------------------------------------

	/**
	 * Returns all top-level files in a source.
	 *
	 * @param int $id
	 * @return array
	 */
	public function getFilesBySourceId($id)
	{
		return blx()->assets->getFilesBySourceId($id);
	}
}
