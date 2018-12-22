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
 * @since 3.0
 */
class RoutesController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        // All route actions require an admin
        $this->requireAdmin();
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

        $uriParts = Craft::$app->getRequest()->getRequiredBodyParam('uriParts');
        $template = Craft::$app->getRequest()->getRequiredBodyParam('template');
        $siteId = Craft::$app->getRequest()->getBodyParam('siteId');
        $routeId = Craft::$app->getRequest()->getBodyParam('routeId');

        if ($siteId === '') {
            $siteId = null;
        }

        $routeRecord = Craft::$app->getRoutes()->saveRoute($uriParts, $template, $siteId, $routeId);

        if ($routeRecord->hasErrors()) {
            return $this->asJson(['errors' => $routeRecord->getErrors()]);
        }

        return $this->asJson([
            'success' => true,
            'routeId' => $routeRecord->id,
            'siteId' => $routeRecord->siteId
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

        $routeId = Craft::$app->getRequest()->getRequiredBodyParam('routeId');
        Craft::$app->getRoutes()->deleteRouteById($routeId);

        return $this->asJson(['success' => true]);
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

        $routeIds = Craft::$app->getRequest()->getRequiredBodyParam('routeIds');
        Craft::$app->getRoutes()->updateRouteOrder($routeIds);

        return $this->asJson(['success' => true]);
    }
}
