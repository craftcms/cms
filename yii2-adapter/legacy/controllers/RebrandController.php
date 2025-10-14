<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\helpers\Assets;
use craft\helpers\FileHelper;
use craft\helpers\Image;
use craft\web\Controller;
use craft\web\UploadedFile;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Edition;
use yii\web\Response;
use function CraftCms\Cms\t;

/**
 * The RebrandController class is a controller that handles various control panel re-branding tasks such as uploading,
 * cropping and deleting site logos and icons.
 * Note that all actions in the controller require an authenticated Craft session via [[allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class RebrandController extends Controller
{
    /**
     * @var array Allowed types of site images.
     */
    private array $_allowedTypes = ['logo', 'icon'];

    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        Edition::require(Edition::Pro);

        return true;
    }

    /**
     * Handles control panel logo and site icon uploads.
     *
     * @return Response
     */
    public function actionUploadSiteImage(): Response
    {
        $this->requireAcceptsJson();
        $this->requireAdmin();
        $type = $this->request->getRequiredBodyParam('type');

        if (!in_array($type, $this->_allowedTypes, true)) {
            return $this->asFailure(t('That is not an allowed image type.'));
        }

        // Grab the uploaded file
        if (($file = UploadedFile::getInstanceByName('image')) === null) {
            return $this->asFailure(t('There was an error uploading your photo'));
        }

        $filename = Assets::prepareAssetName($file->name, true, true);

        if (!Image::canManipulateAsImage($file->getExtension())) {
            return $this->asFailure(t('The uploaded file is not an image.'));
        }

        $targetPath = Craft::$app->getPath()->getRebrandPath() . '/' . $type . '/';

        if (!is_dir($targetPath)) {
            FileHelper::createDirectory($targetPath);
        } else {
            FileHelper::clearDirectory($targetPath);
        }

        $fileDestination = $targetPath . '/' . $filename;

        move_uploaded_file($file->tempName, $fileDestination);

        $imagesService = Craft::$app->getImages();

        if (app(GeneralConfig::class)->sanitizeCpImageUploads) {
            Image::cleanImageByPath($fileDestination);
        }

        $imagesService->loadImage($fileDestination)->scaleToFit(300, 300)->saveAs($fileDestination);
        $html = $this->getView()->renderTemplate("settings/general/_images/$type.twig");

        return $this->asJson([
            'html' => $html,
        ]);
    }

    /**
     * Deletes control panel logo and site icon images.
     *
     * @return Response|null
     */
    public function actionDeleteSiteImage(): ?Response
    {
        $this->requireAdmin();
        $type = $this->request->getRequiredBodyParam('type');

        if (!in_array($type, $this->_allowedTypes, true)) {
            return $this->asFailure(t('That is not an allowed image type.'));
        }

        FileHelper::clearDirectory(Craft::$app->getPath()->getRebrandPath() . '/' . $type);

        $html = $this->getView()->renderTemplate("settings/general/_images/$type.twig");

        return $this->asJson([
            'html' => $html,
        ]);
    }
}
