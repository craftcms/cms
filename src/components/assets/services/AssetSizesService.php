<?php
namespace Blocks;

/**
 *
 */
class AssetSizesService extends BaseApplicationComponent
{
	/**
	 * @var array of AssetSizeModel
	 */
	private $_assetSizes = null;

	/**
	 * Get all asset sizes.
	 *
	 * @return array|null
	 */
	public function getAssetSizes()
	{
		$this->_loadAssetSizes();

		return $this->_assetSizes;
	}

	/**
	 * Get an asset size by it's handle.
	 *
	 * @param $handle
	 * @return AssetSizeModel
	 */
	public function getAssetSize($handle)
	{
		$this->_loadAssetSizes();

		if (isset($this->_assetSizes[$handle]))
		{
			return $this->_assetSizes[$handle];
		}

		$this->_noSizeExists($handle);
	}

	/**
	 * @return array
	 */
	private function _loadAssetSizes()
	{
		if (is_null($this->_assetSizes))
		{
			$this->_assetSizes = array();
			$models = AssetSizeModel::populateModels(AssetSizeRecord::model()->findAll());
			foreach ($models as $model)
			{
				$this->_assetSizes[$model->handle] = $model;
			}
		}
	}

	/**
	 * Saves an asset size.
	 *
	 * @param AssetSizeModel $size
	 * @return bool
	 */
	public function saveSize(AssetSizeModel $size)
	{
		$sizeRecord = $this->_getSizeRecordById($size->id, $size->handle);

		$sizeRecord->name = $size->name;
		$sizeRecord->handle = $size->handle;

		if ($sizeRecord->width != $size->width || $sizeRecord->height != $size->height)
		{
			$sizeRecord->dimensionChangeTime = time();
		}

		$sizeRecord->width = $size->width;
		$sizeRecord->height = $size->height;

		$recordValidates = $sizeRecord->validate();

		if ($recordValidates)
		{
			$sizeRecord->save(false);

			// Now that we have a size ID, save it on the model
			if (!$size->id)
			{
				$size->id = $sizeRecord->id;
			}

			return true;
		}
		else
		{
			$size->addErrors($sizeRecord->getErrors());
			return false;
		}
	}

	/**
	 * Deletes an asset size by it's handle..
	 *
	 * @param string $sizeHandle
	 * @throws \Exception
	 * @return bool
	 */
	public function deleteSizeByHandle($sizeHandle)
	{
		blx()->db->createCommand()->delete('assetsizes', array('handle' => $sizeHandle));
		return true;
	}

	/**
	 * Gets a size's record.
	 *
	 * @param int $id
	 * @param string $handle assumed handle for image size for nicer error messages.
	 * @return AssetSizeRecord
	 */
	private function _getSizeRecordById($id = null, $handle = "")
	{
		if ($id)
		{
			$sizeRecord = AssetSizeRecord::model()->findById($id);

			if (!$sizeRecord)
			{
				$this->_noSizeExists($handle);
			}
		}
		else
		{
			$sizeRecord = new AssetSizeRecord();
		}

		return $sizeRecord;
	}

	/**
	 * Throws a "No size exists" exception.
	 *
	 * @access private
	 * @param int $handle
	 * @throws Exception
	 */
	private function _noSizeExists($handle)
	{
		throw new Exception(Blocks::t("Can't find the size with handle “{handle}”", array('handle' => $handle)));
	}

	/**
	 * Update the asset sizes for the FileModel.
	 *
	 * @param AssetFileModel $fileModel
	 * @param array $sizesToUpdate
	 * @return bool
	 */
	public function updateSizes(AssetFileModel $fileModel, array $sizesToUpdate)
	{
		$sourceType = blx()->assetSources->getSourceTypeById($fileModel->sourceId);
		$imageSource = $sourceType->getImageSourcePath($fileModel);

		if (!IOHelper::fileExists($imageSource))
		{
			return false;
		}


		foreach ($sizesToUpdate as $handle)
		{
			$size = $this->getAssetSize($handle);

			// This will set the time modified to 0 for files that don't exist.
			$timeModified = (int) $sourceType->getTimeSizeModified($fileModel, $handle);

			// Create the size if the file doesn't exist, or if it was created before the image was last updated
			// or if the size dimensions have changed since it was last created
			if ($timeModified < $fileModel->dateModified || $timeModified < $size->dimensionChangeTime)
			{
				$targetFile = AssetsHelper::getTempFilePath(pathinfo($fileModel->filename, PATHINFO_EXTENSION));
				blx()->images->loadImage($imageSource)->resizeTo($size->width, $size->height)->saveAs($targetFile);
				clearstatcache(true, $targetFile);
				$sourceType->putImageSize($fileModel, $handle, $targetFile);
				IOHelper::deleteFile($targetFile);
			}
		}

		return true;
	}
}
