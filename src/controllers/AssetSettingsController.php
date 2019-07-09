<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\models\UserGroup;
use craft\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * The AssetSettingsController class is a controller that handles various asset settings related tasks.
 * Note that all actions in this controller require administrator access in order to execute.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.2
 */
class AssetSettingsController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        // All user settings actions require an admin
        $this->requireAdmin();

        return parent::beforeAction($action);
    }

    /**
     * Saves the system asset settings.
     *
     * @return Response|null
     */
    public function actionSaveAssetSettings()
    {
        $this->requirePostRequest();
        $projectConfig = Craft::$app->getProjectConfig();

        if ($tempVolumeUid = Craft::$app->getRequest()->getBodyParam('tempVolumeUid')) {
            $settings = [
                'tempVolumeUid' => $tempVolumeUid,
            ];
            if ($tempSubpath = trim(Craft::$app->getRequest()->getBodyParam('tempSubpath'), '/\\ ')) {
                $settings['tempSubpath'] = str_replace('\\', '/', $tempSubpath);
            }
            $projectConfig->set('assets', $settings);
        } else {
            $projectConfig->remove('assets');
        }

        Craft::$app->getSession()->setNotice(Craft::t('app', 'Asset settings saved.'));
        return $this->redirectToPostedUrl();
    }
}
