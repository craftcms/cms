<?php
/**
 * @link      http://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license
 */

namespace craft\app\controllers;

use Craft;
use craft\app\helpers\Assets;
use craft\app\helpers\Image;
use craft\app\helpers\Io;
use craft\app\helpers\Url;
use craft\app\web\Controller;
use craft\app\web\UploadedFile;
use yii\web\Response;

Craft::$app->requireEdition(Craft::Client);

/**
 * The RebrandController class is a controller that handles various control panel re-branding tasks such as uploading,
 * cropping and deleting site logos and icons.
 *
 * Note that all actions in the controller require an authenticated Craft session via [[Controller::allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class RebrandController extends Controller
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
     * @return Response
     */
    public function actionUploadSiteImage()
    {
        $this->requireAjaxRequest();
        $this->requireAdmin();
        $type = Craft::$app->getRequest()->getRequiredBodyParam('type');

        if (!in_array($type, $this->_allowedTypes)) {
            return $this->asErrorJson(Craft::t('app',
                'That is not an accepted site image type.'));
        }

        // Upload the file and drop it in the temporary folder
        $file = UploadedFile::getInstanceByName('image-upload');

        try {
            // Make sure a file was uploaded
            if ($file) {
                $filename = Assets::prepareAssetName($file->name);

                if (!Image::isImageManipulatable($file->getExtension())) {
                    throw new BadRequestHttpException('The uploaded file is not an image');
                }

                $folderPath = Craft::$app->getPath()->getTempUploadsPath();
                Io::ensureFolderExists($folderPath);
                Io::clearFolder($folderPath, true);

                $fileDestination = $folderPath.'/'.$filename;

                move_uploaded_file($file->tempName, $fileDestination);

                $imageService = Craft::$app->getImages();

                // Test if we will be able to perform image actions on this image
                if (!$imageService->checkMemoryForImage($fileDestination)) {
                    Io::deleteFile($fileDestination);
                    return $this->asErrorJson(Craft::t('app',
                        'The uploaded image is too large'));
                }

                $imageService->loadImage($fileDestination)->
                scaleToFit(500, 500, false)->
                saveAs($fileDestination);

                list ($width, $height) = Image::getImageSize($fileDestination);

                // If the file is in the format badscript.php.gif perhaps.
                if ($width && $height) {
                    $html = Craft::$app->getView()->renderTemplate('_components/tools/cropper_modal',
                        array(
                            'imageUrl' => Url::getResourceUrl('tempuploads/'.$filename),
                            'width' => $width,
                            'height' => $height,
                            'filename' => $filename
                        )
                    );

                    return $this->asJson(array('html' => $html));
                }
            }
        } catch (BadRequestHttpException $exception) {
            return $this->asErrorJson(Craft::t('app', 'The uploaded file is not an image.'));
        }

        return $this->asErrorJson(Craft::t('app',
            'There was an error uploading your photo'));
    }

    /**
     * Crop user photo.
     *
     * @return Response
     */
    public function actionCropSiteImage()
    {
        $this->requireAjaxRequest();
        $this->requireAdmin();

        $requestService = Craft::$app->getRequest();

        $type = $requestService->getRequiredBodyParam('type');

        if (!in_array($type, $this->_allowedTypes)) {
            $this->asErrorJson(Craft::t('app',
                'That is not a legal site image type.'));
        }

        try {
            $x1 = $requestService->getRequiredBodyParam('x1');
            $x2 = $requestService->getRequiredBodyParam('x2');
            $y1 = $requestService->getRequiredBodyParam('y1');
            $y2 = $requestService->getRequiredBodyParam('y2');
            $source = $requestService->getRequiredBodyParam('source');

            // Strip off any querystring info, if any.
            $source = Url::stripQueryString($source);

            $pathService = Craft::$app->getPath();
            $imageService = Craft::$app->getImages();

            $imagePath = $pathService->getTempUploadsPath().'/'.$source;

            if (Io::fileExists($imagePath) && $imageService->checkMemoryForImage($imagePath)) {
                $targetPath = $pathService->getRebrandPath().'/'.$type.'/';

                Io::ensureFolderExists($targetPath);
                Io::clearFolder($targetPath);

                $imageService
                    ->loadImage($imagePath)
                    ->crop($x1, $x2, $y1, $y2)
                    ->scaleToFit(300, 300, false)
                    ->saveAs($targetPath.$source);

                Io::deleteFile($imagePath);

                $html = Craft::$app->getView()->renderTemplate('settings/general/_images/'.$type);
                return $this->asJson(array('html' => $html));
            }

            Io::deleteFile($imagePath);
        } catch (\Exception $exception) {
            return $this->asErrorJson($exception->getMessage());
        }

        return $this->asErrorJson(Craft::t('app',
            'Something went wrong when processing the logo.'));
    }

    /**
     * Delete logo.
     *
     * @return Response
     */
    public function actionDeleteSiteImage()
    {
        $this->requireAdmin();
        $type = Craft::$app->getRequest()->getRequiredBodyParam('type');

        if (!in_array($type, $this->_allowedTypes)) {
            $this->asErrorJson(Craft::t('app',
                'That is not a legal site image type.'));
        }

        Io::clearFolder(Craft::$app->getPath()->getRebrandPath().$type.'/');

        $html = Craft::$app->getView()->renderTemplate('settings/general/_images/'.$type);

        return $this->asJson(array('html' => $html));
    }
}
