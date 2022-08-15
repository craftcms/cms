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
 * The RoutesController class is a controller that handles various route related tasks such as saving, deleting and
 * re-ordering routes in the control panel.
 * Note that all actions in the controller require an authenticated Craft session via [[allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class RoutesController extends Controller
{
    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        // All route actions require an admin
        $this->requireAdmin();

        return parent::beforeAction($action);
    }

    /**
     * Saves a new or existing route.
     *
     * @return Response
     */
    public function actionSaveRoute(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $uriParts = $this->request->getRequiredBodyParam('uriParts');
        $template = $this->request->getRequiredBodyParam('template');
        $siteUid = $this->request->getBodyParam('siteUid');
        $routeUid = $this->request->getBodyParam('routeUid');

        if ($siteUid === '') {
            $siteUid = null;
        }

        $routeUid = Craft::$app->getRoutes()->saveRoute($uriParts, $template, $siteUid, $routeUid);

        return $this->asSuccess(data: [
            'routeUid' => $routeUid,
            'siteUid' => $siteUid,
        ]);
    }

    /**
     * Deletes a route.
     *
     * @return Response
     */
    public function actionDeleteRoute(): Response
    {
        $this->requirePostRequest();

        $routeUid = $this->request->getRequiredBodyParam('routeUid');
        Craft::$app->getRoutes()->deleteRouteByUid($routeUid);

        return $this->asSuccess();
    }

    /**
     * Updates the route order.
     *
     * @return Response
     */
    public function actionUpdateRouteOrder(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $routeUids = $this->request->getRequiredBodyParam('routeUids');
        Craft::$app->getRoutes()->updateRouteOrder($routeUids);

        return $this->asSuccess();
    }
}
