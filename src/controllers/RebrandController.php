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
	/**
	 * Allowed types of site images.
	 *
	 * @var array
	 */
	private $_allowedTypes = array('logo', 'icon');

	// Public Methods
	// =========================================================================

	/**
	 * Upload a logo for the admin panel.
	 *
	 * @return null
	 */
	public function actionUploadSiteImage()
	{
		$this->requireAjaxRequest();
		$this->requireAdmin();
		$type = craft()->request->getRequiredPost('type');

		if (!in_array($type, $this->_allowedTypes))
		{
			$this->returnErrorJson(Craft::t('That is not an accepted site image type.'));
		}

		// Upload the file and drop it in the temporary folder
		$file = UploadedFile::getInstanceByName('image-upload');

		try
		{
			// Make sure a file was uploaded
			if ($file)
			{
				$fileName = AssetsHelper::cleanAssetName($file->getName());

				if (!ImageHelper::isImageManipulatable($file->getExtensionName()))
				{
					throw new Exception(Craft::t('The uploaded file is not an image.'));
				}

				$folderPath = craft()->path->getTempUploadsPath();
				IOHelper::ensureFolderExists($folderPath);
				IOHelper::clearFolder($folderPath, true);

				move_uploaded_file($file->getTempName(), $folderPath.$fileName);

				// Test if we will be able to perform image actions on this image
				if (!craft()->images->checkMemoryForImage($folderPath.$fileName))
				{
					IOHelper::deleteFile($folderPath.$fileName);
					$this->returnErrorJson(Craft::t('The uploaded image is too large'));
				}

                list ($width, $height) = ImageHelper::getImageSize($folderPath.$fileName);

                if (IOHelper::getExtension($fileName) != 'svg')
                {
                    craft()->images->cleanImage($folderPath.$fileName);
                }
                else
                {
                    craft()->images->
                        loadImage($folderPath.$fileName)->
                        saveAs($folderPath.$fileName);
                }

				$constraint = 500;

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
							'constraint' => $constraint,
                            'fileName' => $fileName
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
	public function actionCropSiteImage()
	{
		$this->requireAjaxRequest();
		$this->requireAdmin();
		$type = craft()->request->getRequiredPost('type');

		if (!in_array($type, $this->_allowedTypes))
		{
			$this->returnErrorJson(Craft::t('That is not a legal site image type.'));
		}

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
				$targetPath = craft()->path->getRebrandPath().$type.'/';

				IOHelper::ensureFolderExists($targetPath);
                IOHelper::clearFolder($targetPath);

                craft()->images
						->loadImage($imagePath)
						->crop($x1, $x2, $y1, $y2)
						->scaleToFit(300, 300, false)
						->saveAs($targetPath.$source);

				IOHelper::deleteFile($imagePath);

				$html = craft()->templates->render('settings/general/_images/'.$type);
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
	public function actionDeleteSiteImage()
	{
		$this->requireAdmin();
		$type = craft()->request->getRequiredPost('type');

		if (!in_array($type, $this->_allowedTypes))
		{
			$this->returnErrorJson(Craft::t('That is not a legal site image type.'));
		}

		IOHelper::clearFolder(craft()->path->getRebrandPath().$type.'/');

		$html = craft()->templates->render('settings/general/_images/'.$type);

		$this->returnJson(array('html' => $html));
	}
}
