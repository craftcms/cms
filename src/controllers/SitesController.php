<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\models\Site;
use craft\models\SiteGroup;
use craft\web\assets\sites\SitesAsset;
use craft\web\Controller;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

/**
 * The SitesController class is a controller that handles various actions related to categories and category
 * groups, such as creating, editing and deleting them.
 * Note that all actions in the controller require an authenticated Craft session via [[allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class SitesController extends Controller
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        // All actions require an admin account
        $this->requireAdmin();
    }

    /**
     * Site settings index.
     *
     * @param int|null $groupId
     * @return Response
     * @throws NotFoundHttpException if $groupId is invalid
     */
    public function actionSettingsIndex(int $groupId = null): Response
    {
        $sitesService = Craft::$app->getSites();
        $allGroups = $sitesService->getAllGroups();

        if ($groupId) {
            if (($group = $sitesService->getGroupById($groupId)) === null) {
                throw new NotFoundHttpException('Invalid site group ID: ' . $groupId);
            }
            $sites = $sitesService->getSitesByGroupId($groupId);
        } else {
            $group = null;
            $sites = $sitesService->getAllSites();
        }

        $crumbs = [
            ['label' => Craft::t('app', 'Settings'), 'url' => UrlHelper::cpUrl('settings')],
        ];

        $view = $this->getView();
        $view->registerAssetBundle(SitesAsset::class);
        $view->registerTranslations('app', [
            'Could not create the group:',
            'Group renamed.',
            'Could not rename the group:',
            'What do you want to name the group?',
            'Are you sure you want to delete this group?',
            'What do you want to do with any content that is only available in {language}?',
            'Transfer it to:',
            'Delete it',
            'Delete {site}',
        ]);

        return $this->renderTemplate('settings/sites/index', compact(
            'crumbs',
            'allGroups',
            'group',
            'sites'
        ));
    }

    // Groups
    // -------------------------------------------------------------------------

    /**
     * Saves a site group.
     *
     * @return Response
     * @throws BadRequestHttpException
     */
    public function actionSaveGroup(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $sitesService = Craft::$app->getSites();
        $groupId = $this->request->getBodyParam('id');

        if ($groupId) {
            $group = $sitesService->getGroupById($groupId);
            if (!$group) {
                throw new BadRequestHttpException("Invalid site group ID: $groupId");
            }
        } else {
            $group = new SiteGroup();
        }

        $group->name = $this->request->getRequiredBodyParam('name');

        if (!Craft::$app->getSites()->saveGroup($group)) {
            return $this->asJson([
                'errors' => $group->getErrors(),
            ]);
        }

        return $this->asJson([
            'success' => true,
            'group' => $group->getAttributes(),
        ]);
    }

    /**
     * Deletes a site group.
     *
     * @return Response
     */
    public function actionDeleteGroup(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $groupId = $this->request->getRequiredBodyParam('id');
        $success = Craft::$app->getSites()->deleteGroupById($groupId);

        $this->setSuccessFlash(Craft::t('app', 'Group deleted.'));

        return $this->asJson([
            'success' => $success,
        ]);
    }

    // Sites
    // -------------------------------------------------------------------------

    /**
     * Edit a category group.
     *
     * @param int|null $siteId The site’s ID, if editing an existing site
     * @param Site|null $site The site being edited, if there were any validation errors
     * @param int|null $groupId The default group ID that the site should be saved in
     * @return Response
     * @throws NotFoundHttpException if the requested site cannot be found
     * @throws ServerErrorHttpException if no site groups exist
     */
    public function actionEditSite(int $siteId = null, Site $site = null, int $groupId = null): Response
    {
        $sitesService = Craft::$app->getSites();

        $brandNewSite = false;

        if ($siteId !== null) {
            if ($site === null) {
                $site = $sitesService->getSiteById($siteId);

                if (!$site) {
                    throw new NotFoundHttpException('Site not found');
                }
            }

            $title = trim($site->name) ?: Craft::t('app', 'Edit Site');
        } else {
            if ($site === null) {
                $site = new Site();
                $site->language = $sitesService->getPrimarySite()->language;
                $brandNewSite = true;
            }

            $title = Craft::t('app', 'Create a new site');
        }

        // Groups
        // ---------------------------------------------------------------------

        $allGroups = $sitesService->getAllGroups();

        if (empty($allGroups)) {
            throw new ServerErrorHttpException('No site groups exist');
        }

        if ($groupId === null) {
            $groupId = $site->groupId ?? $allGroups[0]->id;
        }

        $siteGroup = $sitesService->getGroupById($groupId);

        if ($siteGroup === null) {
            throw new NotFoundHttpException('Site group not found');
        }

        $groupOptions = [];

        foreach ($allGroups as $group) {
            $groupOptions[] = [
                'value' => $group->id,
                'label' => $group->name
            ];
        }

        // Page setup + render
        // ---------------------------------------------------------------------

        // Breadcrumbs
        $crumbs = [
            [
                'label' => Craft::t('app', 'Settings'),
                'url' => UrlHelper::url('settings')
            ],
            [
                'label' => Craft::t('app', 'Sites'),
                'url' => UrlHelper::url('settings/sites')
            ]
        ];

        $languageOptions = [];

        foreach (Craft::$app->getI18n()->getAllLocales() as $locale) {
            $languageOptions[] = [
                'value' => $locale->id,
                'label' => Craft::t('app', '{id} – {name}', [
                    'name' => $locale->getDisplayName(Craft::$app->language),
                    'id' => $locale->id
                ])
            ];
        }

        return $this->renderTemplate('settings/sites/_edit', compact(
            'brandNewSite',
            'title',
            'crumbs',
            'site',
            'groupId',
            'groupOptions',
            'languageOptions'
        ));
    }

    /**
     * Saves a site.
     *
     * @return Response|null
     * @throws BadRequestHttpException
     */
    public function actionSaveSite()
    {
        $this->requirePostRequest();

        $sitesService = Craft::$app->getSites();
        $siteId = $this->request->getBodyParam('siteId');

        if ($siteId) {
            $site = $sitesService->getSiteById($siteId);
            if (!$site) {
                throw new BadRequestHttpException("Invalid site ID: $siteId");
            }
        } else {
            $site = new Site();
            $site->id = $this->request->getBodyParam('siteId');
        }

        $site->groupId = $this->request->getBodyParam('group');
        $site->name = $this->request->getBodyParam('name');
        $site->handle = $this->request->getBodyParam('handle');
        $site->language = $this->request->getBodyParam('language');
        $site->primary = (bool)$this->request->getBodyParam('primary');
        $site->enabled = $site->primary || (bool)$this->request->getBodyParam('enabled');
        $site->hasUrls = (bool)$this->request->getBodyParam('hasUrls');
        $site->baseUrl = $site->hasUrls ? $this->request->getBodyParam('baseUrl') : null;

        // Save it
        if (!$sitesService->saveSite($site)) {
            $this->setFailFlash(Craft::t('app', 'Couldn’t save the site.'));

            // Send the site back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'site' => $site
            ]);

            return null;
        }

        $this->setSuccessFlash(Craft::t('app', 'Site saved.'));
        return $this->redirectToPostedUrl($site);
    }

    /**
     * Reorders sites.
     *
     * @return Response
     */
    public function actionReorderSites(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $siteIds = Json::decode($this->request->getRequiredBodyParam('ids'));
        Craft::$app->getSites()->reorderSites($siteIds);

        return $this->asJson(['success' => true]);
    }

    /**
     * Deletes a site.
     *
     * @return Response
     */
    public function actionDeleteSite(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $siteId = $this->request->getRequiredBodyParam('id');
        $transferContentTo = $this->request->getBodyParam('transferContentTo');

        Craft::$app->getSites()->deleteSiteById($siteId, $transferContentTo);

        return $this->asJson(['success' => true]);
    }
}
