<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\base\Element;
use craft\elements\Asset;
use craft\errors\AssetException;
use craft\errors\AssetLogicException;
use craft\errors\UploadFailedException;
use craft\errors\VolumeException;
use craft\fields\Assets as AssetsField;
use craft\helpers\App;
use craft\helpers\Assets;
use craft\helpers\Db;
use craft\helpers\Image;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\i18n\Formatter;
use craft\image\Raster;
use craft\models\VolumeFolder;
use craft\web\Controller;
use craft\web\UploadedFile;
use yii\base\ErrorException;
use yii\base\Exception;
use yii\base\NotSupportedException;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;
use ZipArchive;

/** @noinspection ClassOverridesFieldOfSuperClassInspection */

/**
 * The AssetIndexes class is a controller that handles asset indexing tasks.
 * Note that all actions in the controller require an authenticated Craft session as well as the relevant permissions.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class AssetIndexesController extends Controller
{
    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        // No permission no bueno
        $this->requirePermission('utility:asset-indexes');
        $this->requireAcceptsJson();

        return parent::beforeAction($action);
    }

    public function actionStartIndexing(): Response
    {
        $request = Craft::$app->getRequest();
        $volumes = (array)$request->getRequiredBodyParam('volumes');
        $cacheRemoteImages = (bool)$request->getBodyParam('cacheImages', false);
        $asQueueJob = (bool)$request->getBodyParam('useQueue', false);

        if (empty($volumes)) {
            return $this->asErrorJson(Craft::t('app', 'No volumes specified'));
        }

        $indexingSession = Craft::$app->getAssetIndexer()->startIndexingSession($volumes, $cacheRemoteImages, $asQueueJob);
        $sessionData = $indexingSession->toArray();
        $sessionData['dateCreated'] = $indexingSession->dateCreated->format('Y-m-d H:i');
        $sessionData['dateUpdated'] = $indexingSession->dateUpdated->format('Y-m-d H:i');

        return $this->asJson(['session' => $sessionData]);
    }

    public function actionStopIndexingSession(): Response
    {
        $sessionId = (int) Craft::$app->getRequest()->getRequiredBodyParam('sessionId');

        if (empty($sessionId)) {
            return $this->asErrorJson(Craft::t('app', 'No indexing session specified'));
        }

        Craft::$app->getAssetIndexer()->stopIndexingSession($sessionId);
        return $this->asJson(['stop' => $sessionId]);
    }
}
