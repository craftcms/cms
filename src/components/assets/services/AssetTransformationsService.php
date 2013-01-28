<?php
namespace Blocks;

/**
 *
 */
class AssetTransformationsService extends BaseApplicationComponent
{
	/**
	 * @var array of AssetTransformationModel
	 */
	private $_assetTransformations = null;

	/**
	 * Get all asset transformations.
	 *
	 * @return array|null
	 */
	public function getAssetTransformations()
	{
		$this->_loadAssetTransformations();

		return $this->_assetTransformations;
	}

	/**
	 * Get an asset transformation by it's handle.
	 *
	 * @param $handle
	 * @return AssetTransformationModel
	 */
	public function getAssetTransformation($handle)
	{
		$this->_loadAssetTransformations();

		if (isset($this->_assetTransformations[$handle]))
		{
			return $this->_assetTransformations[$handle];
		}

		$this->_noTransformationExists($handle);
	}

	/**
	 * @return array
	 */
	private function _loadAssetTransformations()
	{
		if (is_null($this->_assetTransformations))
		{
			$this->_assetTransformations = array();
			$models = AssetTransformationModel::populateModels(AssetTransformationRecord::model()->findAll());

			foreach ($models as $model)
			{
				$this->_assetTransformations[$model->handle] = $model;
			}
		}
	}

	/**
	 * Saves an asset transformation.
	 *
	 * @param AssetTransformationModel $transformation
	 * @return bool
	 */
	public function saveTransformation(AssetTransformationModel $transformation)
	{
		$transformationRecord = $this->_getTransformationRecordById($transformation->id, $transformation->handle);

		$transformationRecord->name = $transformation->name;
		$transformationRecord->handle = $transformation->handle;
		$transformationRecord->mode = $transformation->mode;

		if ($transformationRecord->width != $transformation->width || $transformationRecord->height != $transformation->height || $transformationRecord->mode != $transformation->mode)
		{
			$transformationRecord->dimensionChangeTime = new DateTime('@'.time());
		}

		$transformationRecord->width = $transformation->width;
		$transformationRecord->height = $transformation->height;

		$recordValidates = $transformationRecord->validate();

		if ($recordValidates)
		{
			$transformationRecord->save(false);

			// Now that we have a transformation ID, save it on the model
			if (!$transformation->id)
			{
				$transformation->id = $transformationRecord->id;
			}

			return true;
		}
		else
		{
			$transformation->addErrors($transformationRecord->getErrors());
			return false;
		}
	}

	/**
	 * Deletes an asset transformation by it's id.
	 *
	 * @param int $transformationId
	 * @throws \Exception
	 * @return bool
	 */
	public function deleteTransformation($transformationId)
	{
		blx()->db->createCommand()->delete('assettransformations', array('id' => $transformationId));
		return true;
	}

	/**
	 * Gets a transformation's record.
	 *
	 * @param int $id
	 * @param string $handle assumed handle for image transformation for nicer error messages.
	 * @return AssetTransformationRecord
	 */
	private function _getTransformationRecordById($id = null, $handle = "")
	{
		if ($id)
		{
			$transformationRecord = AssetTransformationRecord::model()->findById($id);

			if (!$transformationRecord)
			{
				$this->_noTransformationExists($handle);
			}
		}
		else
		{
			$transformationRecord = new AssetTransformationRecord();
		}

		return $transformationRecord;
	}

	/**
	 * Throws a "No transformation exists" exception.
	 *
	 * @access private
	 * @param int $handle
	 * @throws Exception
	 */
	private function _noTransformationExists($handle)
	{
		throw new Exception(Blocks::t("Can't find the transformation with handle “{handle}”", array('handle' => $handle)));
	}

	/**
	 * Update the asset transformations for the FileModel.
	 *
	 * @param AssetFileModel $fileModel
	 * @param array $transformationsToUpdate
	 * @return bool
	 */
	public function updateTransformations(AssetFileModel $fileModel, array $transformationsToUpdate)
	{
		$sourceType = blx()->assetSources->getSourceTypeById($fileModel->sourceId);
		$imageSource = $sourceType->getImageSourcePath($fileModel);

		if (!IOHelper::fileExists($imageSource))
		{
			return false;
		}


		foreach ($transformationsToUpdate as $handle)
		{
			$transformation = $this->getAssetTransformation($handle);

			$timeModified = $sourceType->getTimeTransformationModified($fileModel, $handle);

			// Create the transformation if the file doesn't exist, or if it was created before the image was last updated
			// or if the transformation dimensions have changed since it was last created
			if (!$timeModified || $timeModified < $fileModel->dateModified || $timeModified < $transformation->dimensionChangeTime)
			{
				$targetFile = AssetsHelper::getTempFilePath(pathinfo($fileModel->filename, PATHINFO_EXTENSION));
				switch ($transformation->mode)
				{
					case 'scaleToFit':
					{
						blx()->images->loadImage($imageSource)->scale($transformation->width, $transformation->height)->saveAs($targetFile);
						break;
					}

					case 'scaleAndCrop':
					{
						blx()->images->loadImage($imageSource)->scaleAndCrop($transformation->width, $transformation->height)->saveAs($targetFile);
						break;

					}
					case 'stretchToFit':
					{
						blx()->images->loadImage($imageSource)->resizeTo($transformation->width, $transformation->height)->saveAs($targetFile);
						break;
					}

				}
				clearstatcache(true, $targetFile);
				$sourceType->putImageTransformation($fileModel, $handle, $targetFile);
				IOHelper::deleteFile($targetFile);
			}
		}

		return true;
	}
}
