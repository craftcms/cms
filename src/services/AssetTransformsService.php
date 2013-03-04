<?php
namespace Craft;

/**
 *
 */
class AssetTransformsService extends BaseApplicationComponent
{
	/**
	 * @var array of AssetTransformModel
	 */
	private $_assetTransforms = null;

	/**
	 * Get all asset transforms.
	 *
	 * @return array|null
	 */
	public function getAssetTransforms()
	{
		$this->_loadAssetTransforms();

		return $this->_assetTransforms;
	}

	/**
	 * Get an asset transform by it's handle.
	 *
	 * @param $handle
	 * @return AssetTransformModel
	 */
	public function getAssetTransform($handle)
	{
		$this->_loadAssetTransforms();

		if (isset($this->_assetTransforms[$handle]))
		{
			return $this->_assetTransforms[$handle];
		}

		$this->_noTransformExists($handle);
	}

	/**
	 * @return array
	 */
	private function _loadAssetTransforms()
	{
		if (is_null($this->_assetTransforms))
		{
			$this->_assetTransforms = array();
			$models = AssetTransformModel::populateModels(AssetTransformRecord::model()->findAll());

			foreach ($models as $model)
			{
				$this->_assetTransforms[$model->handle] = $model;
			}
		}
	}

	/**
	 * Saves an asset transform.
	 *
	 * @param AssetTransformModel $transform
	 * @return bool
	 */
	public function saveTransform(AssetTransformModel $transform)
	{
		$transformRecord = $this->_getTransformRecordById($transform->id, $transform->handle);

		$transformRecord->name = $transform->name;
		$transformRecord->handle = $transform->handle;
		$transformRecord->mode = $transform->mode;

		if ($transformRecord->width != $transform->width || $transformRecord->height != $transform->height || $transformRecord->mode != $transform->mode)
		{
			$transformRecord->dimensionChangeTime = new DateTime('@'.time());
		}

		$transformRecord->width = $transform->width;
		$transformRecord->height = $transform->height;

		$recordValidates = $transformRecord->validate();

		if ($recordValidates)
		{
			$transformRecord->save(false);

			// Now that we have a transform ID, save it on the model
			if (!$transform->id)
			{
				$transform->id = $transformRecord->id;
			}

			return true;
		}
		else
		{
			$transform->addErrors($transformRecord->getErrors());
			return false;
		}
	}

	/**
	 * Deletes an asset transform by it's id.
	 *
	 * @param int $transformId
	 * @throws \Exception
	 * @return bool
	 */
	public function deleteTransform($transformId)
	{
		craft()->db->createCommand()->delete('assettransforms', array('id' => $transformId));
		return true;
	}

	/**
	 * Gets a transform's record.
	 *
	 * @param int $id
	 * @param string $handle assumed handle for image transform for nicer error messages.
	 * @return AssetTransformRecord
	 */
	private function _getTransformRecordById($id = null, $handle = "")
	{
		if ($id)
		{
			$transformRecord = AssetTransformRecord::model()->findById($id);

			if (!$transformRecord)
			{
				$this->_noTransformExists($handle);
			}
		}
		else
		{
			$transformRecord = new AssetTransformRecord();
		}

		return $transformRecord;
	}

	/**
	 * Throws a "No transform exists" exception.
	 *
	 * @access private
	 * @param int $handle
	 * @throws Exception
	 */
	private function _noTransformExists($handle)
	{
		throw new Exception(Craft::t("Can't find the transform with handle “{handle}”", array('handle' => $handle)));
	}

	/**
	 * Update the asset transforms for the FileModel.
	 *
	 * @param AssetFileModel $fileModel
	 * @param array $transformsToUpdate
	 * @return bool
	 */
	public function updateTransforms(AssetFileModel $fileModel, array $transformsToUpdate)
	{
		if (!in_array(IOHelper::getExtension($fileModel), Image::getAcceptedExtensions()))
		{
			return true;
		}

		$sourceType = craft()->assetSources->getSourceTypeById($fileModel->sourceId);
		$imageSource = $sourceType->getImageSourcePath($fileModel);

		if (!IOHelper::fileExists($imageSource))
		{
			return false;
		}


		foreach ($transformsToUpdate as $handle)
		{
			$transform = $this->getAssetTransform($handle);

			$timeModified = $sourceType->getTimeTransformModified($fileModel, $handle);

			// Create the transform if the file doesn't exist, or if it was created before the image was last updated
			// or if the transform dimensions have changed since it was last created
			if (!$timeModified || $timeModified < $fileModel->dateModified || $timeModified < $transform->dimensionChangeTime)
			{
				$targetFile = AssetsHelper::getTempFilePath(IOHelper::getExtension($fileModel->filename));
				switch ($transform->mode)
				{
					case 'scaleToFit':
					{
						craft()->images->loadImage($imageSource)->scale($transform->width, $transform->height)->saveAs($targetFile);
						break;
					}

					case 'scaleAndCrop':
					{
						craft()->images->loadImage($imageSource)->scaleAndCrop($transform->width, $transform->height)->saveAs($targetFile);
						break;

					}
					case 'stretchToFit':
					{
						craft()->images->loadImage($imageSource)->resizeTo($transform->width, $transform->height)->saveAs($targetFile);
						break;
					}

				}
				clearstatcache(true, $targetFile);
				$sourceType->putImageTransform($fileModel, $handle, $targetFile);
				IOHelper::deleteFile($targetFile);
			}
		}

		return true;
	}
}
