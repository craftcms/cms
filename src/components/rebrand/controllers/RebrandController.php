<?php
namespace Blocks;

/**
 * Handles rebranding tasks
 */
class RebrandController extends BaseController
{
	/**
	 * Upload a logo for the admin panel.
	 */
	public function actionUploadLogo()
	{
		$this->requireAjaxRequest();
		$this->requireAdmin();

		// Upload the file and drop it in the temporary folder
		$uploader = new \qqFileUploader();

		try
		{
			// Make sure a file was uploaded
			if ($uploader->file && $uploader->file->getSize())
			{
				$folderPath = blx()->path->getTempUploadsPath();
				IOHelper::ensureFolderExists($folderPath);
				IOHelper::clearFolder($folderPath);

				$fileName = IOHelper::cleanFilename($uploader->file->getName());

				$uploader->file->save($folderPath.$fileName);

				// Test if we will be able to perform image actions on this image
				if (!blx()->images->setMemoryForImage($folderPath.$fileName))
				{
					IOHelper::deleteFile($folderPath.$fileName);
					$this->returnErrorJson(Blocks::t('The uploaded image is too large'));
				}

				$constraint = 500;
				list ($width, $height) = getimagesize($folderPath.$fileName);

				// If the file is in the format badscript.php.gif perhaps.
				if ($width && $height)
				{
					// Never scale up the images, so make the scaling factor always <= 1
					$factor = min($constraint / $width, $constraint / $height, 1);

					$html = blx()->templates->render('_components/tools/cropper_modal',
						array(
							'imageUrl' => UrlHelper::getResourceUrl('tempuploads/'.$fileName),
							'width' => round($width * $factor),
							'height' => round($height * $factor),
							'factor' => $factor
						)
					);

					$this->returnJson(array('html' => $html));
				}
			}
		}
		catch (Exception $exception)
		{
			$this->returnErrorJson($exception->getMessage());
		}

		$this->returnErrorJson(Blocks::t('There was an error uploading your photo'));
	}

	/**
	 * Crop user photo.
	 */
	public function actionCropLogo()
	{
		$this->requireAjaxRequest();
		$this->requireAdmin();

		try
		{
			$x1 = blx()->request->getRequiredPost('x1');
			$x2 = blx()->request->getRequiredPost('x2');
			$y1 = blx()->request->getRequiredPost('y1');
			$y2 = blx()->request->getRequiredPost('y2');
			$source = blx()->request->getRequiredPost('source');

			// Strip off any querystring info.
			$source = substr($source, 0, strpos($source, '?'));
			$imagePath = blx()->path->getTempUploadsPath().$source;

			if (IOHelper::fileExists($imagePath) && blx()->images->setMemoryForImage($imagePath))
			{
				$targetPath = blx()->path->getStoragePath().'logo/';

				IOHelper::ensureFolderExists($targetPath);

					IOHelper::clearFolder($targetPath);
					blx()->images
						->loadImage($imagePath)
						->crop($x1, $x2, $y1, $y2)
						->scale(300, 300)
						->saveAs($targetPath.$source);

				IOHelper::deleteFile($imagePath);

				$this->returnJson(array('success' => true));
			}
			IOHelper::deleteFile($imagePath);
		}
		catch (Exception $exception)
		{
			$this->returnErrorJson($exception->getMessage());
		}

		$this->returnErrorJson(Blocks::t('Something went wrong when processing the logo.'));
	}

	/**
	 * Delete logo.
	 */
	public function actionDeleteLogo()
	{
		$this->requireAdmin();
		IOHelper::clearFolder(blx()->path->getStoragePath().'logo/');
		$this->returnJson(array('success' => true));
	}
}
