<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\web\Controller;
use yii\base\Response;

/**
 * Manages the Project Config.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class ProjectConfigController extends Controller
{
    /**
     * @inheritDoc
     */
    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        $this->requirePostRequest();
        $this->requirePermission('utility:project-config');
        return true;
    }

    /**
     * Discards any changes to the project config files.
     *
     * @return Response
     * @since 3.5.6
     */
    public function actionDiscard(): Response
    {
        Craft::$app->getProjectConfig()->regenerateYamlFromConfig();
        $this->setSuccessFlash(Craft::t('app', 'Project config YAML changes discarded.'));
        return $this->redirectToPostedUrl();
    }

    /**
     * Rebuilds the project config.
     *
     * @return Response
     * @since 3.5.6
     */
    public function actionRebuild(): Response
    {
        Craft::$app->getProjectConfig()->rebuild();
        $this->setSuccessFlash(Craft::t('app', 'Project config rebuilt successfully.'));
        return $this->redirectToPostedUrl();
    }
}
