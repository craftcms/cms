<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\controllers;

use Craft;
use craft\app\helpers\Json;
use craft\app\web\Controller;
use yii\web\Response;

Craft::$app->requireEdition(Craft::Pro);

/**
 * The LocalizationController class is a controller that handles various localization related tasks such adding,
 * deleting and re-ordering locales in the control panel.
 *
 * Note that all actions in the controller require an authenticated Craft session via [[Controller::allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class LocalizationController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        // All localization related actions require an admin
        $this->requireAdmin();
    }

    /**
     * Adds a new a locale.
     *
     * @return Response
     */
    public function actionAddLocale()
    {
        $this->requirePostRequest();
        $this->requireAjaxRequest();

        $localeId = Craft::$app->getRequest()->getRequiredBodyParam('id');
        $success = Craft::$app->getI18n()->addSiteLocale($localeId);

        return $this->asJson(['success' => $success]);
    }

    /**
     * Saves the new locale order.
     *
     * @return Response
     */
    public function actionReorderLocales()
    {
        $this->requirePostRequest();
        $this->requireAjaxRequest();

        $localeIds = Json::decode(Craft::$app->getRequest()->getRequiredBodyParam('ids'));
        $success = Craft::$app->getI18n()->reorderSiteLocales($localeIds);

        return $this->asJson(['success' => $success]);
    }

    /**
     * Deletes a locale.
     *
     * @return Response
     */
    public function actionDeleteLocale()
    {
        $this->requirePostRequest();
        $this->requireAjaxRequest();

        $localeId = Craft::$app->getRequest()->getRequiredBodyParam('id');
        $transferContentTo = Craft::$app->getRequest()->getBodyParam('transferContentTo');

        $success = Craft::$app->getI18n()->deleteSiteLocale($localeId, $transferContentTo);

        return $this->asJson(['success' => $success]);
    }
}
