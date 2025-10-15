<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\helpers\Cp;
use craft\helpers\UrlHelper;
use craft\web\assets\sites\SitesAsset;
use craft\web\Controller;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Facades\SiteGroups;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Str;
use Illuminate\Validation\ValidationException;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;
use function CraftCms\Cms\t;

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
    private bool $readOnly;

    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        $viewActions = ['settings-index', 'edit-site'];
        if (in_array($action->id, $viewActions)) {
            // Some actions require admin but not allowAdminChanges
            $this->requireAdmin(false);
        } else {
            // All other actions require an admin & allowAdminChanges
            $this->requireAdmin();
        }

        $this->readOnly = !app(GeneralConfig::class)->allowAdminChanges;

        return true;
    }

    /**
     * Site settings index.
     *
     * @param int|null $groupId
     * @return Response
     * @throws NotFoundHttpException if $groupId is invalid
     */
    public function actionSettingsIndex(?int $groupId = null): Response
    {
        if ($groupId) {
            if (($group = SiteGroups::getGroupById($groupId)) === null) {
                throw new NotFoundHttpException('Invalid site group ID: ' . $groupId);
            }
            $sites = Sites::getSitesByGroupId($groupId);
        } else {
            $group = null;
            $sites = Sites::getAllSites();
        }

        $crumbs = [
            ['label' => t('Settings'), 'url' => UrlHelper::cpUrl('settings')],
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

        return $this->renderTemplate('settings/sites/index.twig', [
            'crumbs' => $crumbs,
            'group' => $group,
            'sites' => $sites,
            'readOnly' => $this->readOnly,
        ]);
    }

    // Groups
    // -------------------------------------------------------------------------

    /**
     * Returns the HTML and JS for a rename-site-group modal.
     *
     * @return Response
     * @since 3.7.0
     */
    public function actionRenameGroupField(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $view = Craft::$app->getView();
        $view->startJsBuffer();
        $html = $view->namespaceInputs(fn() => Cp::autosuggestFieldHtml([
            'label' => t('Group Name'),
            'instructions' => t('What this group will be called in the control panel.'),
            'id' => 'name',
            'name' => 'name',
            'value' => $this->request->getBodyParam('name') ?? '',
            'suggestEnvVars' => true,
            'required' => true,
        ]), 'name' . Str::random(10));
        $js = $view->clearJsBuffer();

        return $this->asJson(compact('html', 'js'));
    }

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

        $groupId = $this->request->getBodyParam('id');

        if ($groupId) {
            $group = SiteGroups::getGroupById($groupId);
            if (!$group) {
                throw new BadRequestHttpException("Invalid site group ID: $groupId");
            }
        } else {
            $group = new \CraftCms\Cms\Site\Data\SiteGroup();
        }

        $group->setName($this->request->getRequiredBodyParam('name'));

        try {
            $group->validate($this->request->getBodyParams());
        } catch (ValidationException $e) {
            return $this->asFailure(data: [
                'errors' => array_values(array_map(fn($errors) => reset($errors), $e->errors())),
            ]);
        }

        SiteGroups::saveGroup($group);

        $attr = $group->toArray();
        $attr['name'] = t($attr['name'], category: 'site');

        return $this->asSuccess(data: [
            'group' => $attr,
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

        if (!SiteGroups::deleteGroupById($groupId)) {
            return $this->asFailure();
        }

        return $this->asSuccess(t('Group deleted.'));
    }

    // Sites
    // -------------------------------------------------------------------------

    /**
     * Edit a category group.
     *
     * @param int|null $siteId The site’s ID, if editing an existing site
     * @param Site|null $siteData The site being edited, if there were any validation errors
     * @param int|null $groupId The default group ID that the site should be saved in
     * @return Response
     * @throws NotFoundHttpException if the requested site cannot be found
     * @throws ServerErrorHttpException if no site groups exist
     */
    public function actionEditSite(?int $siteId = null, ?Site $siteData = null, ?int $groupId = null): Response
    {
        if ($siteId === null && $this->readOnly) {
            throw new ForbiddenHttpException('Administrative changes are disallowed in this environment.');
        }

        $brandNewSite = false;

        if ($siteId !== null) {
            if ($siteData === null) {
                $siteData = Sites::getSiteById($siteId);

                if (!$siteData) {
                    throw new NotFoundHttpException('Site not found');
                }
            }

            $title = trim($siteData->getName()) ?: t('Edit Site');
        } else {
            if ($siteData === null) {
                $siteData = new Site(
                    name: '',
                    handle: '',
                    language: Sites::getPrimarySite()->getLanguage(false),
                );
                $brandNewSite = true;
            }

            $title = t('Create a new site');
        }

        // Groups
        // ---------------------------------------------------------------------

        $allGroups = SiteGroups::getAllGroups();

        if ($allGroups->isEmpty()) {
            throw new ServerErrorHttpException('No site groups exist');
        }

        if ($groupId === null) {
            $groupId = $siteData->groupId ?? $allGroups[0]->id;
        }

        $siteGroup = SiteGroups::getGroupById($groupId);

        if ($siteGroup === null) {
            throw new NotFoundHttpException('Site group not found');
        }

        $groupOptions = [];

        foreach ($allGroups as $group) {
            $groupOptions[] = [
                'value' => $group->id,
                'label' => t($group->getName(), category: 'site'),
            ];
        }

        // Page setup + render
        // ---------------------------------------------------------------------

        // Breadcrumbs
        $crumbs = [
            [
                'label' => t('Settings'),
                'url' => UrlHelper::url('settings'),
            ],
            [
                'label' => t('Sites'),
                'url' => UrlHelper::url('settings/sites'),
            ],
        ];

        return $this->renderTemplate('settings/sites/_edit.twig', [
            'brandNewSite' => $brandNewSite,
            'title' => $title,
            'crumbs' => $crumbs,
            'site' => $siteData,
            'groupId' => $groupId,
            'groupOptions' => $groupOptions,
            'readOnly' => $this->readOnly,
        ]);
    }

    /**
     * Saves a site.
     *
     * @return Response|null
     * @throws BadRequestHttpException
     */
    public function actionSaveSite(): ?Response
    {
        $this->requirePostRequest();

        $siteId = $this->request->getBodyParam('siteId');

        if ($siteId) {
            $site = Sites::getSiteById($siteId);
            if (!$site) {
                throw new BadRequestHttpException("Invalid site ID: $siteId");
            }
        } else {
            $site = new Site(
                name: '',
                handle: '',
                language: Sites::getPrimarySite()->getLanguage(false),
            );
            $site->id = $this->request->getBodyParam('siteId');
        }

        $site->groupId = $this->request->getBodyParam('group');
        $site->setName($this->request->getBodyParam('name'));
        $site->handle = $this->request->getBodyParam('handle');
        $site->setLanguage($this->request->getBodyParam('language'));
        $site->primary = (bool)$this->request->getBodyParam('primary');
        $site->setEnabled($site->primary ? true : $this->request->getBodyParam('enabled', true));
        $site->hasUrls = (bool)$this->request->getBodyParam('hasUrls');
        $site->setBaseUrl($site->hasUrls ? $this->request->getBodyParam('baseUrl') : null);

        // Save it
        if (!Sites::saveSite($site)) {
            $this->setFailFlash(t('Couldn’t save the site.'));

            // Send the site back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'siteModel' => $site,
            ]);

            return null;
        }

        $this->setSuccessFlash(t('Site saved.'));
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

        /** @var int[] $siteIds */
        $siteIds = Json::decode($this->request->getRequiredBodyParam('ids'));
        Sites::reorderSites($siteIds);

        return $this->asSuccess();
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

        Sites::deleteSiteById($siteId, $transferContentTo);

        return $this->asSuccess();
    }
}
