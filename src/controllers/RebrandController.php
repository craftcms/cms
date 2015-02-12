<?php
namespace Craft;

craft()->requireEdition(Craft::Client);

/**
 * The RebrandController class is a controller that handles various control panel re-branding tasks such as uploading,
 * cropping and delete custom logos for displaying on the login page.
 *
 * Note that all actions in the controller require an authenticated Craft session via {@link BaseController::allowAnonymous}.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.controllers
 * @since     1.0
 */
class RebrandController extends BaseController
{
	// Public Methods
	// =========================================================================

	/**
	 * Upload a logo for the admin panel.
	 *
	 * @return null
	 */
	public function actionUploadLogo()
	{
		$this->requireAjaxRequest();
		$this->requireAdmin();

		// Upload the file and drop it in the temporary folder
		$file = $_FILES['image-upload'];

		try
		{
			// Make sure a file was uploaded
			if (!empty($file['name']) && !empty($file['size'])  )
			{
				$folderPath = craft()->path->getTempUploadsPath();
				IOHelper::ensureFolderExists($folderPath);
				IOHelper::clearFolder($folderPath, true);

				$fileName = AssetsHelper::cleanAssetName($file['name']);

				move_uploaded_file($file['tmp_name'], $folderPath.$fileName);

				// Test if we will be able to perform image actions on this image
				if (!craft()->images->checkMemoryForImage($folderPath.$fileName))
				{
					IOHelper::deleteFile($folderPath.$fileName);
					$this->returnErrorJson(Craft::t('The uploaded image is too large'));
				}

				craft()->images->cleanImage($folderPath.$fileName);

				$constraint = 500;
				list ($width, $height) = getimagesize($folderPath.$fileName);

				// If the file is in the format badscript.php.gif perhaps.
				if ($width && $height)
				{
					// Never scale up the images, so make the scaling factor always <= 1
					$factor = min($constraint / $width, $constraint / $height, 1);

					$html = craft()->templates->render('_components/tools/cropper_modal',
						array(
							'imageUrl' => UrlHelper::getResourceUrl('tempuploads/'.$fileName),
							'width' => round($width * $factor),
							'height' => round($height * $factor),
							'factor' => $factor,
							'constraint' => $constraint
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

		$this->returnErrorJson(Craft::t('There was an error uploading your photo'));
	}

	/**
	 * Crop user photo.
	 *
	 * @return null
	 */
	public function actionCropLogo()
	{
		$this->requireAjaxRequest();
		$this->requireAdmin();

		try
		{
			$x1 = craft()->request->getRequiredPost('x1');
			$x2 = craft()->request->getRequiredPost('x2');
			$y1 = craft()->request->getRequiredPost('y1');
			$y2 = craft()->request->getRequiredPost('y2');
			$source = craft()->request->getRequiredPost('source');

			// Strip off any querystring info, if any.
			$source = UrlHelper::stripQueryString($source);

			$imagePath = craft()->path->getTempUploadsPath().$source;

			if (IOHelper::fileExists($imagePath) && craft()->images->checkMemoryForImage($imagePath))
			{
				$targetPath = craft()->path->getStoragePath().'logo/';

				IOHelper::ensureFolderExists($targetPath);

					IOHelper::clearFolder($targetPath);
					craft()->images
						->loadImage($imagePath)
						->crop($x1, $x2, $y1, $y2)
						->scaleToFit(300, 300, false)
						->saveAs($targetPath.$source);

				IOHelper::deleteFile($imagePath);

				$html = craft()->templates->render('settings/general/_logo');
				$this->returnJson(array('html' => $html));
			}
			IOHelper::deleteFile($imagePath);
		}
		catch (Exception $exception)
		{
			$this->returnErrorJson($exception->getMessage());
		}

		$this->returnErrorJson(Craft::t('Something went wrong when processing the logo.'));
	}

	/**
	 * Delete logo.
	 *
	 * @return null
	 */
	public function actionDeleteLogo()
	{
		$this->requireAdmin();
		IOHelper::clearFolder(craft()->path->getStoragePath().'logo/');

		$html = craft()->templates->render('settings/general/_logo');
		$this->returnJson(array('html' => $html));

	}
}
