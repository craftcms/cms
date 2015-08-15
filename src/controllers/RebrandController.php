<?php
/**
 * @link      http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license
 */

namespace craft\app\controllers;

use Craft;
use craft\app\errors\Exception;
use craft\app\helpers\AssetsHelper;
use craft\app\helpers\ImageHelper;
use craft\app\helpers\IOHelper;
use craft\app\helpers\UrlHelper;
use craft\app\web\Controller;

Craft::$app->requireEdition(Craft::Client);

/**
 * The RebrandController class is a controller that handles various control panel re-branding tasks such as uploading,
 * cropping and delete custom logos for displaying on the login page.
 *
 * Note that all actions in the controller require an authenticated Craft session via [[Controller::allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class RebrandController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * Upload a logo for the admin panel.
     *
     * @return mixed
     */
    public function actionUploadLogo()
    {
        $this->requireAjaxRequest();
        $this->requireAdmin();

        // Upload the file and drop it in the temporary folder
        $file = $_FILES['image-upload'];

        try {
            // Make sure a file was uploaded
            if (!empty($file['name']) && !empty($file['size'])) {
                $folderPath = Craft::$app->getPath()->getTempUploadsPath();
                IOHelper::ensureFolderExists($folderPath);
                IOHelper::clearFolder($folderPath, true);

                $filename = AssetsHelper::prepareAssetName($file['name']);

                move_uploaded_file($file['tmp_name'], $folderPath.'/'.$filename);

                // Test if we will be able to perform image actions on this image
                if (!Craft::$app->getImages()->checkMemoryForImage($folderPath.'/'.$filename)) {
                    IOHelper::deleteFile($folderPath.'/'.$filename);

                    return $this->asErrorJson(Craft::t('app', 'The uploaded image is too large'));
                }

                list ($width, $height) = ImageHelper::getImageSize($folderPath.'/'.$filename);

                if (IOHelper::getExtension($filename) != 'svg') {
                    Craft::$app->getImages()->cleanImage($folderPath.'/'.$filename);
                } else {
                    // Resave svg files as png
                    $newFilename = preg_replace('/\.svg$/i', '.png', $filename);

                    Craft::$app->getImages()
                        ->loadImage($folderPath.'/'.$filename, $width, $height)
                        ->saveAs($folderPath.'/'.$newFilename);

                    IOHelper::deleteFile($folderPath.'/'.$filename);
                    $filename = $newFilename;
                }

                $constraint = 500;

                // If the file is in the format bad-script.php.gif perhaps.
                if ($width && $height) {
                    // Never scale up the images, so make the scaling factor always <= 1
                    $factor = min($constraint / $width, $constraint / $height, 1);

                    $html = Craft::$app->getView()->renderTemplate('_components/tools/cropper_modal',
                        [
                            'imageUrl' => UrlHelper::getResourceUrl('tempuploads/'.$filename),
                            'width' => round($width * $factor),
                            'height' => round($height * $factor),
                            'factor' => $factor,
                            'constraint' => $constraint,
                            'fileName' => $filename
                        ]
                    );

                    return $this->asJson(['html' => $html]);
                }
            }
        } catch (Exception $exception) {
            return $this->asErrorJson($exception->getMessage());
        }

        return $this->asErrorJson(Craft::t('app', 'There was an error uploading your photo'));
    }

    /**
     * Crop user photo.
     *
     * @return mixed
     */
    public function actionCropLogo()
    {
        $this->requireAjaxRequest();
        $this->requireAdmin();

        try {
            $x1 = Craft::$app->getRequest()->getRequiredBodyParam('x1');
            $x2 = Craft::$app->getRequest()->getRequiredBodyParam('x2');
            $y1 = Craft::$app->getRequest()->getRequiredBodyParam('y1');
            $y2 = Craft::$app->getRequest()->getRequiredBodyParam('y2');
            $source = Craft::$app->getRequest()->getRequiredBodyParam('source');

            // Strip off any query string info, if any.
            $source = UrlHelper::stripQueryString($source);

            $imagePath = Craft::$app->getPath()->getTempUploadsPath().'/'.$source;

            if (IOHelper::fileExists($imagePath) && Craft::$app->getImages()->checkMemoryForImage($imagePath)) {
                $targetPath = Craft::$app->getPath()->getStoragePath().'/logo';

                IOHelper::ensureFolderExists($targetPath);
                IOHelper::clearFolder($targetPath);

                Craft::$app->getImages()
                    ->loadImage($imagePath, 300, 300)
                    ->crop($x1, $x2, $y1, $y2)
                    ->scaleToFit(300, 300, false)
                    ->saveAs($targetPath.'/'.$source);

                IOHelper::deleteFile($imagePath);

                $html = Craft::$app->getView()->renderTemplate('settings/general/_logo');

                return $this->asJson(['html' => $html]);
            }
            IOHelper::deleteFile($imagePath);
        } catch (Exception $exception) {
            return $this->asErrorJson($exception->getMessage());
        }

        return $this->asErrorJson(Craft::t('app', 'Something went wrong when processing the logo.'));
    }

    /**
     * Delete logo.
     *
     * @return mixed
     */
    public function actionDeleteLogo()
    {
        $this->requireAdmin();
        IOHelper::clearFolder(Craft::$app->getPath()->getStoragePath().'/logo');

        $html = Craft::$app->getView()->renderTemplate('settings/general/_logo');

        return $this->asJson(['html' => $html]);
    }
}
