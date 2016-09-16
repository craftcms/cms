<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\controllers;

use Craft;
use craft\app\base\Element;
use craft\app\base\Field;
use craft\app\helpers\Json;
use craft\app\helpers\Url;
use craft\app\elements\Category;
use craft\app\models\CategoryGroup;
use craft\app\models\CategoryGroup_SiteSettings;
use craft\app\models\Site;
use craft\app\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

/**
 * The SitesController class is a controller that handles various actions related to categories and category
 * groups, such as creating, editing and deleting them.
 *
 * Note that all actions in the controller require an authenticated Craft session via [[Controller::allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class SitesController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        // All actions require an admin account
        $this->requireAdmin();
    }

    /**
     * Site settings index.
     *
     * @return string The rendering result
     */
    public function actionSettingsIndex()
    {
        $allSites = Craft::$app->getSites()->getAllSites();

        return $this->renderTemplate('settings/sites/index', [
            'allSites' => $allSites
        ]);
    }

    /**
     * Edit a category group.
     *
     * @param integer $siteId The site’s ID, if editing an existing site
     * @param Site    $site   The site being edited, if there were any validation errors
     *
     * @return string The rendering result
     * @throws NotFoundHttpException if the requested site cannot be found
     */
    public function actionEditSite($siteId = null, Site $site = null)
    {
        // Breadcrumbs
        $variables['crumbs'] = [
            [
                'label' => Craft::t('app', 'Settings'),
                'url' => Url::getUrl('settings')
            ],
            [
                'label' => Craft::t('app', 'Sites'),
                'url' => Url::getUrl('settings/sites')
            ]
        ];

        $variables['brandNewSite'] = false;

        if ($siteId !== null) {
            if ($site === null) {
                $site = Craft::$app->getSites()->getSiteById($siteId);

                if (!$site) {
                    throw new NotFoundHttpException('Site not found');
                }
            }

            $variables['title'] = $site->name;
        } else {
            if ($site === null) {
                $site = new Site();
                $site->language = Craft::$app->getSites()->getPrimarySite()->language;
                $variables['brandNewSite'] = true;
            }

            $variables['title'] = Craft::t('app', 'Create a new site');
        }

        $variables['site'] = $site;

        $variables['languageOptions'] = [];

        foreach (Craft::$app->getI18n()->getAllLocales() as $locale) {
            $variables['languageOptions'][] = [
                'value' => $locale->id,
                'label' => Craft::t('app', '{id} – {name}', [
                    'name' => $locale->getDisplayName(Craft::$app->language),
                    'id' => $locale->id
                ])
            ];
        }

        return $this->renderTemplate('settings/sites/_edit', $variables);
    }

    /**
     * Saves a site.
     *
     * @return Response|null
     */
    public function actionSaveSite()
    {
        $this->requirePostRequest();

        $site = new Site();

        // Set the simple stuff
        $request = Craft::$app->getRequest();
        $site->id = $request->getBodyParam('siteId');
        $site->name = $request->getBodyParam('name');
        $site->handle = $request->getBodyParam('handle');
        $site->language = $request->getBodyParam('language');
        $site->hasUrls = (bool)$request->getBodyParam('hasUrls');
        $site->baseUrl = $site->hasUrls ? $request->getBodyParam('baseUrl') : null;

        // Save it
        if (Craft::$app->getSites()->saveSite($site)) {
            Craft::$app->getSession()->setNotice(Craft::t('app', 'Site saved.'));

            return $this->redirectToPostedUrl($site);
        }

        Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t save the site.'));

        // Send the site back to the template
        Craft::$app->getUrlManager()->setRouteParams([
            'site' => $site
        ]);

        return null;
    }

    /**
     * Reorders sites.
     *
     * @return Response
     */
    public function actionReorderSites()
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $siteIds = Json::decode(Craft::$app->getRequest()->getRequiredBodyParam('ids'));
        Craft::$app->getSites()->reorderSites($siteIds);

        return $this->asJson(['success' => true]);
    }

    /**
     * Deletes a site.
     *
     * @return Response
     */
    public function actionDeleteSite()
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $request = Craft::$app->getRequest();
        $siteId = $request->getRequiredBodyParam('id');
        $transferContentTo = $request->getBodyParam('transferContentTo');

        Craft::$app->getSites()->deleteSiteById($siteId, $transferContentTo);

        return $this->asJson(['success' => true]);
    }
}
