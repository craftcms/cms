<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\web\Controller;
use yii\base\Exception;
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
        // This controller is only available to the CP
        if (!$this->request->getIsCpRequest()) {
            throw new NotFoundHttpException();
        }

        $this->requirePostRequest();

        return true;
    }

    /**
     * Ignore any changes to project config files by
     *
     * @return Response
     * @throws Exception
     */
    public function actionIgnore(): Response
    {
        Craft::$app->getProjectConfig()->ignorePendingChanges();
        return $this->redirectToPostedUrl($this->data['returnUrl'] ?? Craft::$app->getConfig()->getGeneral()->getPostCpLoginRedirect());
    }
}
