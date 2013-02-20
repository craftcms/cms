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
	 * @param string|null $indexBy
	 * @return array
	 */
	public function getAllSources($indexBy = null)
	{
		return blx()->assetSources->getAllSources($indexBy);
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

	// -------------------------------------------
	// Folders
	// -------------------------------------------

	/**
	 * Returns a sources top level folder
	 * @param $id
	 * @return AssetFolderModel|null
	 */
	public function getFolderBySourceId($id)
	{
		return blx()->assets->findFolder(array(
			'sourceId' => $id,
			'parentId' => null
		));
	}

	// -------------------------------------------
	// Transformations
	// -------------------------------------------

	/**
	 * Get all asset transformations.
	 *
	 * @return array|null
	 */
	public function getAllAssetTransformations()
	{
		return blx()->assetTransformations->getAssetTransformations();
	}

	/**
	 * Get asset transformation by it's handle.
	 *
	 * @param $handle
	 * @return null
	 */
	public function getTransformationByHandle($handle)
	{
		return blx()->assetTransformations->getAssetTransformation($handle);
	}

	/**
	 * Return a list of possible transformation scale modes
	 * @return array
	 */
	public function getTransformationModes()
	{
		return array(
			'scaleToFit' => Blocks::t("Scale to fit"),
			'scaleAndCrop' => Blocks::t("Scale and crop"),
			'stretchToFit' => Blocks::t("Stretch to fit"),
		);
	}

	/**
	 * Returns all folders in a structured way
	 */
	public function getAllFolders()
	{
		$tree = blx()->assets->getFolderTree();
		return $tree;
	}
}
