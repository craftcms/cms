<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\controllers;

use Craft;
use craft\app\errors\Exception;
use craft\app\helpers\AssetsHelper;
use craft\app\helpers\IOHelper;
use craft\app\helpers\UrlHelper;

Craft::$app->requireEdition(Craft::Client);

/**
 * The RebrandController class is a controller that handles various control panel re-branding tasks such as uploading,
 * cropping and delete custom logos for displaying on the login page.
 *
 * Note that all actions in the controller require an authenticated Craft session via [[BaseController::allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
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
				$folderPath = Craft::$app->path->getTempUploadsPath();
				IOHelper::ensureFolderExists($folderPath);
				IOHelper::clearFolder($folderPath, true);

				$fileName = AssetsHelper::cleanAssetName($file['name']);

				move_uploaded_file($file['tmp_name'], $folderPath.$fileName);

				// Test if we will be able to perform image actions on this image
				if (!Craft::$app->images->checkMemoryForImage($folderPath.$fileName))
				{
					IOHelper::deleteFile($folderPath.$fileName);
					$this->returnErrorJson(Craft::t('The uploaded image is too large'));
				}

				Craft::$app->images->cleanImage($folderPath.$fileName);

				$constraint = 500;
				list ($width, $height) = getimagesize($folderPath.$fileName);

				// If the file is in the format badscript.php.gif perhaps.
				if ($width && $height)
				{
					// Never scale up the images, so make the scaling factor always <= 1
					$factor = min($constraint / $width, $constraint / $height, 1);

					$html = Craft::$app->templates->render('_components/tools/cropper_modal',
						[
							'imageUrl' => UrlHelper::getResourceUrl('tempuploads/'.$fileName),
							'width' => round($width * $factor),
							'height' => round($height * $factor),
							'factor' => $factor,
							'constraint' => $constraint
						]
					);

					$this->returnJson(['html' => $html]);
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
			$x1 = Craft::$app->request->getRequiredBodyParam('x1');
			$x2 = Craft::$app->request->getRequiredBodyParam('x2');
			$y1 = Craft::$app->request->getRequiredBodyParam('y1');
			$y2 = Craft::$app->request->getRequiredBodyParam('y2');
			$source = Craft::$app->request->getRequiredBodyParam('source');

			// Strip off any querystring info, if any.
			if (($qIndex = mb_strpos($source, '?')) !== false)
			{
				$source = mb_substr($source, 0, mb_strpos($source, '?'));
			}

			// It's possible there are & in the case of a site that isn't using rewriting the URL and the resource is
			// being cached.  I.E. http://craft.dev/index.php?p=admin/resources/temp/logo.png&x=H1UP9g5TO
			// In this case, $source is logo.png&x=H1UP9g5TO
			if (($qIndex = mb_strpos($source, '&')) !== false)
			{
				$source = mb_substr($source, 0, mb_strpos($source, '&'));
			}

			$imagePath = Craft::$app->path->getTempUploadsPath().$source;

			if (IOHelper::fileExists($imagePath) && Craft::$app->images->checkMemoryForImage($imagePath))
			{
				$targetPath = Craft::$app->path->getStoragePath().'logo/';

				IOHelper::ensureFolderExists($targetPath);

					IOHelper::clearFolder($targetPath);
					Craft::$app->images
						->loadImage($imagePath)
						->crop($x1, $x2, $y1, $y2)
						->scaleToFit(300, 300, false)
						->saveAs($targetPath.$source);

				IOHelper::deleteFile($imagePath);

				$html = Craft::$app->templates->render('settings/general/_logo');
				$this->returnJson(['html' => $html]);
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
		IOHelper::clearFolder(Craft::$app->path->getStoragePath().'logo/');

		$html = Craft::$app->templates->render('settings/general/_logo');
		$this->returnJson(['html' => $html]);

	}
}
