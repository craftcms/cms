<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\web\Controller;
use yii\web\Response;

/**
 * The AssetSettingsController class is a controller that handles various asset settings related tasks.
 * Note that all actions in this controller require administrator access in order to execute.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.2.0
 */
class AssetSettingsController extends Controller
{
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

        if ($tempVolumeUid = $this->request->getBodyParam('tempVolumeUid')) {
            $settings = [
                'tempVolumeUid' => $tempVolumeUid,
            ];
            if ($tempSubpath = trim($this->request->getBodyParam('tempSubpath'), '/\\ ')) {
                $settings['tempSubpath'] = str_replace('\\', '/', $tempSubpath);
            }
            $projectConfig->set('assets', $settings, 'Update Temporary Upload Volume settings.');
        } else {
            $projectConfig->remove('assets', 'Update Temporary Upload Volume settings.');
        }

        $this->setSuccessFlash(Craft::t('app', 'Asset settings saved.'));
        return $this->redirectToPostedUrl();
    }
}
