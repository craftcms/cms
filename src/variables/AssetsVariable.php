<?php
namespace Craft;

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
		$sourceTypes = craft()->assetSources->getAllSourceTypes();
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
		$sourceType = craft()->assetSources->getSourceType($class);

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
		$sourceType = craft()->assetSources->populateSourceType($source);

		if ($sourceType)
		{
			return new AssetSourceTypeVariable($sourceType);
		}

		return null;
	}

	/**
	 * Returns all asset sources.
	 *
	 * @param string|null $indexBy
	 * @return array
	 */
	public function getAllSources($indexBy = null)
	{
		return craft()->assetSources->getAllSources($indexBy);
	}

	/**
	 * Return all source ids.
	 *
	 * @return array
	 */
	public function getAllSourceIds()
	{
		return craft()->assetSources->getAllSourceIds();
	}

	/**
	 * Return all viewable source ids.
	 *
	 * @return array
	 */
	public function getViewableSourceIds()
	{
		return craft()->assetSources->getViewableSourceIds();
	}

	/**
	 * Return all viewable sources.
	 *
	 * @param null $indexBy
	 * @return array
	 */
	public function getViewableSources($indexBy = null)
	{
		return craft()->assetSources->getViewableSources($indexBy);
	}

	/**
	 * Return total number of sources.
	 *
	 * @return int
	 */
	public function getTotalSources()
	{
		return craft()->assetSources->getTotalSources();
	}

	/**
	 * Return total number of viewable sources.
	 *
	 * @return int
	 */
	public function getTotalViewableSources()
	{
		return craft()->assetSources->getTotalViewableSources();
	}

	/**
	 * Gets an asset source by its ID.
	 *
	 * @param int $id
	 * @return AssetSourceModel|null
	 */
	public function getSourceById($id)
	{
		return craft()->assetSources->getSourceById($id);
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
		return craft()->assets->getFilesBySourceId($id);
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
		return craft()->assets->findFolder(array(
			'sourceId' => $id,
			'parentId' => null
		));
	}

	// -------------------------------------------
	// Transforms
	// -------------------------------------------

	/**
	 * Get all asset transforms.
	 *
	 * @return array|null
	 */
	public function getAllAssetTransforms()
	{
		return craft()->assetTransforms->getAssetTransforms();
	}

	/**
	 * Get asset transform by it's handle.
	 *
	 * @param $handle
	 * @return null
	 */
	public function getTransformByHandle($handle)
	{
		return craft()->assetTransforms->getAssetTransform($handle);
	}

	/**
	 * Return a list of possible transform scale modes
	 * @return array
	 */
	public function getTransformModes()
	{
		return array(
			'crop'    => Craft::t('Scale and crop'),
			'fit'     => Craft::t('Scale to fit'),
			'stretch' => Craft::t('Stretch to fit'),
		);
	}

	/**
	 * Returns all folders that a user is allowed to see in a structured way
	 */
	public function getAllFolders()
	{
		$tree = craft()->assets->getFolderTree(craft()->assetSources->getViewableSourceIds());
		return $tree;
	}
}
