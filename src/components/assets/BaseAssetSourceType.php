<?php
namespace Blocks;

/**
 * Asset source base class
 */
abstract class BaseAssetSourceType extends BaseComponent
{
	/**
	 * The type of component this is.
	 *
	 * @access protected
	 * @var string
	 */
	protected $componentType = 'AssetSourceType';

	/**
	 * Starts an indexing session
	 * @param $sessionId
	 * @return array
	 */
	abstract public function startIndex($sessionId);

	/**
	 * Process an indexing session
	 * @param $sessionId
	 * @param $offset
	 * @return mixed
	 */
	abstract public function processIndex($sessionId, $offset);

	/**
	 * Insert a file from path in folder
	 * @param AssetFolderModel $folder
	 * @param $filePath
	 * @param $fileName
	 * @return AssetFileModel
	 * @throws Exception
	 */
	abstract protected function _insertFileInFolder(AssetFolderModel $folder, $filePath, $fileName);

	/**
	 * Get a name replacement for a filename already taken in a folder
	 * @param AssetFolderModel $folder
	 * @param $fileName
	 * @return mixed
	 */
	//abstract protected function _getNameReplacement(AssetFolderModel $folder, $fileName);

	/**
	 * Return a result object for prompting the user about filename conflicts
	 * @param string $fileName the cause of all trouble
	 * @return object
	 */
	protected function _getUserPromptOptions($fileName)
	{
		return (object) array(
			'message' => Blocks::t('File "{file}" already exists at target location', $fileName),
			'choices' => array(
				array('value' => AssetsHelper::ActionKeepBoth, 'title' => Blocks::t('Rename the new file and keep both')),
				array('value' => AssetsHelper::ActionReplace, 'title' => Blocks::t('Replace the existing file')),
				array('value' => AssetsHelper::ActionCancel, 'title' => Blocks::t('Keep the original file'))
			)
		);
	}

	/**
	 * Clean up a filename
	 * @param $fileName
	 * @return mixed
	 */
	protected function _cleanupFilename($fileName)
	{
		$fileName = ltrim($fileName, '.');
		return preg_replace('/[^a-z0-9\.\-_]/i', '_', $fileName);
	}

	/**
	 * @param AssetFolderModel $folder
	 * @return object
	 * @throws Exception
	 */
	public function uploadFile($folder)
	{
		// upload the file and drop it in the temporary folder
		$uploader = new \qqFileUploader();

		// make sure a file was uploaded
		if (! $uploader->file)
		{
			throw new Exception(Blocks::t('No file was uploaded'));
		}

		$size = $uploader->file->getSize();

		// make sure the file isn't empty
		if (! $size)
		{
			throw new Exception(Blocks::t('Uploaded file was empty'));
		}

		// Save the file to a temp location and pass this on to the source type implementation
		$filePath = AssetsHelper::getTempFilePath();
		$uploader->file->save($filePath);

		if ($filename = $this->_insertFileInFolder($folder, $filePath, $uploader->file->getName())) {

		//}

		/*
		// naming conflict. create a new file and ask the user what to do with it
		if ($response->getStatus() == AssetOperationResponseModel::StatusConflict)
		{
			$newFileName = $this->_getNameReplacement($folder, $uploader->file->getName());
			$conflictResponse = $response;
			$response = $this->_insertFileInFolder($folder, $filePath, $newFileName);
		}

		if ($response->getStatus() == AssetOperationResponseModel::StatusSuccess)
		{*/
			//$filename = pathinfo($response->getResponseData()->fileName, PATHINFO_BASENAME);

			$fileModel = new AssetFileModel();
			$fileModel->sourceId = $this->model->id;
			$fileModel->folderId = $folder->id;
			$fileModel->filename = pathinfo($filename, PATHINFO_BASENAME);
			$fileModel->kind = IOHelper::getFileKind(pathinfo($filename, PATHINFO_EXTENSION));
			$fileModel->size = filesize($filePath);
			$fileModel->dateModified = filemtime($filePath);

			if ($fileModel->kind == 'image')
			{
				list ($width, $height) = getimagesize($filePath);
				$fileModel->width = $width;
				$fileModel->height = $height;
			}

			$fileModel->id = blx()->assets->storeFile($fileModel);
			IOHelper::deleteFile($filePath);

			// now that we have stored all this information, we have to send back the original conflict response
			/*if (isset($conflictResponse))
			{
				$response = $conflictResponse;
			}

			$response->setResponseDataItem('file_id', $fileModel->id);
		}
		else
		{*/
			IOHelper::deleteFile($filePath);
			return true;
		}
		return false;//$response;
	}
}