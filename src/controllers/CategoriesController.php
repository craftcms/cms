<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\base\Element;
use craft\elements\Category;
use craft\errors\InvalidElementException;
use craft\events\ElementEvent;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\models\CategoryGroup;
use craft\models\CategoryGroup_SiteSettings;
use craft\models\Site;
use craft\web\Controller;
use yii\base\Exception;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

/**
 * The CategoriesController class is a controller that handles various actions related to categories and category
 * groups, such as creating, editing and deleting them.
 * Note that all actions in the controller require an authenticated Craft session via [[allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class CategoriesController extends Controller
{
    /**
     * @event ElementEvent The event that is triggered when a category’s template is rendered for Live Preview.
     */
    const EVENT_PREVIEW_CATEGORY = 'previewCategory';

    // Category Groups
    // -------------------------------------------------------------------------

    /**
     * Category groups index.
     *
     * @return Response
     */
    public function actionGroupIndex(): Response
    {
        $this->requireAdmin();

        $groups = Craft::$app->getCategories()->getAllGroups();

        return $this->renderTemplate('settings/categories/index', [
            'categoryGroups' => $groups
        ]);
    }

    /**
     * Edit a category group.
     *
     * @param int|null $groupId The category group’s ID, if editing an existing group.
     * @param CategoryGroup|null $categoryGroup The category group being edited, if there were any validation errors.
     * @return Response
     * @throws NotFoundHttpException if the requested category group cannot be found
     */
    public function actionEditCategoryGroup(int $groupId = null, CategoryGroup $categoryGroup = null): Response
    {
        $this->requireAdmin();

        $variables = [];

        // Breadcrumbs
        $variables['crumbs'] = [
            [
                'label' => Craft::t('app', 'Settings'),
                'url' => UrlHelper::url('settings')
            ],
            [
                'label' => Craft::t('app', 'Categories'),
                'url' => UrlHelper::url('settings/categories')
            ]
        ];

        $variables['brandNewGroup'] = false;

        if ($groupId !== null) {
            if ($categoryGroup === null) {
                $categoryGroup = Craft::$app->getCategories()->getGroupById($groupId);

                if (!$categoryGroup) {
                    throw new NotFoundHttpException('Category group not found');
                }
            }

            $variables['title'] = trim($categoryGroup->name) ?: Craft::t('app', 'Edit Category Group');
        } else {
            if ($categoryGroup === null) {
                $categoryGroup = new CategoryGroup();
                $variables['brandNewGroup'] = true;
            }

            $variables['title'] = Craft::t('app', 'Create a new category group');
        }

        $variables['groupId'] = $groupId;
        $variables['categoryGroup'] = $categoryGroup;

        return $this->renderTemplate('settings/categories/_edit', $variables);
    }

    /**
     * Save a category group.
     *
     * @return Response|null
     * @throws BadRequestHttpException
     */
    public function actionSaveGroup()
    {
        $this->requirePostRequest();
        $this->requireAdmin();

        $categoriesService = Craft::$app->getCategories();
        $groupId = $this->request->getBodyParam('groupId');

        if ($groupId) {
            $group = $categoriesService->getGroupById($groupId);
            if (!$group) {
                throw new BadRequestHttpException("Invalid category group ID: $groupId");
            }
        } else {
            $group = new CategoryGroup();
        }

        // Main group settings
        $group->name = $this->request->getBodyParam('name');
        $group->handle = $this->request->getBodyParam('handle');
        $group->maxLevels = $this->request->getBodyParam('maxLevels');

        // Site-specific settings
        $allSiteSettings = [];

        foreach (Craft::$app->getSites()->getAllSites() as $site) {
            $postedSettings = $this->request->getBodyParam('sites.' . $site->handle);

            $siteSettings = new CategoryGroup_SiteSettings();
            $siteSettings->siteId = $site->id;

            if ($siteSettings->hasUrls = !empty($postedSettings['uriFormat'])) {
                $siteSettings->uriFormat = $postedSettings['uriFormat'];
                $siteSettings->template = $postedSettings['template'] ?? null;
            }

            $allSiteSettings[$site->id] = $siteSettings;
        }

        $group->setSiteSettings($allSiteSettings);

        // Group the field layout
        $fieldLayout = Craft::$app->getFields()->assembleLayoutFromPost();
        $fieldLayout->type = Category::class;
        $group->setFieldLayout($fieldLayout);

        // Save it
        if (!$categoriesService->saveGroup($group)) {
            $this->setFailFlash(Craft::t('app', 'Couldn’t save the category group.'));

            // Send the category group back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'categoryGroup' => $group
            ]);

            return null;
        }

        $this->setSuccessFlash(Craft::t('app', 'Category group saved.'));
        return $this->redirectToPostedUrl($group);
    }

    /**
     * Deletes a category group.
     *
     * @return Response
     */
    public function actionDeleteCategoryGroup(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();
        $this->requireAdmin();

        $groupId = $this->request->getRequiredBodyParam('id');

        Craft::$app->getCategories()->deleteGroupById($groupId);

        return $this->asJson(['success' => true]);
    }

    // Categories
    // -------------------------------------------------------------------------

    /**
     * Displays the category index page.
     *
     * @param string|null $groupHandle The category group’s handle.
     * @return Response
     * @throws ForbiddenHttpException if the user is not permitted to edit categories
     */
    public function actionCategoryIndex(string $groupHandle = null): Response
    {
        $groups = Craft::$app->getCategories()->getEditableGroups();

        if (empty($groups)) {
            throw new ForbiddenHttpException('User not permitted to edit categories');
        }

        $this->view->registerTranslations('app', [
            'New category',
        ]);

        return $this->renderTemplate('categories/_index', [
            'groupHandle' => $groupHandle,
            'groups' => $groups
        ]);
    }

    /**
     * Displays the category edit page.
     *
     * @param string $groupHandle The category group’s handle.
     * @param int|null $categoryId The category’s ID, if editing an existing category.
     * @param string|null $siteHandle The site handle, if specified.
     * @param Category|null $category The category being edited, if there were any validation errors.
     * @return Response
     * @throws NotFoundHttpException if the requested site handle is invalid
     */
    public function actionEditCategory(string $groupHandle, int $categoryId = null, string $siteHandle = null, Category $category = null): Response
    {
        $variables = [
            'groupHandle' => $groupHandle,
            'categoryId' => $categoryId,
            'category' => $category
        ];

        if ($siteHandle !== null) {
            $variables['site'] = Craft::$app->getSites()->getSiteByHandle($siteHandle);

            if (!$variables['site']) {
                throw new NotFoundHttpException('Invalid site handle: ' . $siteHandle);
            }
        }

        $this->_prepEditCategoryVariables($variables);

        /** @var Site $site */
        $site = $variables['site'];
        /** @var Category $category */
        $category = $variables['category'];

        $this->_enforceEditCategoryPermissions($category);

        // Parent Category selector variables
        // ---------------------------------------------------------------------

        if ((int)$variables['group']->maxLevels !== 1) {
            $variables['elementType'] = Category::class;

            // Define the parent options criteria
            $variables['parentOptionCriteria'] = [
                'siteId' => $site->id,
                'groupId' => $variables['group']->id,
                'status' => null,
            ];

            if ($variables['group']->maxLevels) {
                if ($category->id) {
                    // Figure out how deep the ancestors go
                    $maxDepth = Category::find()
                        ->select('level')
                        ->descendantOf($category)
                        ->anyStatus()
                        ->leaves()
                        ->scalar();
                    $depth = 1 + ($maxDepth ?: $category->level) - $category->level;
                } else {
                    $depth = 1;
                }

                $variables['parentOptionCriteria']['level'] = '<= ' . ($variables['group']->maxLevels - $depth);
            }

            if ($category->id !== null) {
                // Prevent the current category, or any of its descendants, from being options
                $excludeIds = Category::find()
                    ->descendantOf($category)
                    ->anyStatus()
                    ->ids();

                $excludeIds[] = $category->id;
                $variables['parentOptionCriteria']['where'] = [
                    'not in',
                    'elements.id',
                    $excludeIds
                ];
            }

            // Get the initially selected parent
            $parentId = $this->request->getParam('parentId');

            if ($parentId === null && $category->id !== null) {
                $parentId = $category->getAncestors(1)
                    ->anyStatus()
                    ->ids();
            }

            if (is_array($parentId)) {
                $parentId = reset($parentId) ?: null;
            }

            if ($parentId) {
                $variables['parent'] = Craft::$app->getCategories()->getCategoryById($parentId, $site->id);
            }
        }

        // Other variables
        // ---------------------------------------------------------------------

        // Body class
        $variables['bodyClass'] = 'edit-category site--' . $site->handle;

        // Page title
        if ($category->id === null) {
            $variables['title'] = Craft::t('app', 'Create a new category');
        } else {
            $variables['docTitle'] = $variables['title'] = trim($category->title) ?: Craft::t('app', 'Edit Category');
        }

        // Breadcrumbs
        $variables['crumbs'] = [
            [
                'label' => Craft::t('app', 'Categories'),
                'url' => UrlHelper::url('categories')
            ],
            [
                'label' => Craft::t('site', $variables['group']->name),
                'url' => UrlHelper::url('categories/' . $variables['group']->handle)
            ]
        ];

        /** @var Category $ancestor */
        foreach ($category->getAncestors()->all() as $ancestor) {
            $variables['crumbs'][] = [
                'label' => $ancestor->title,
                'url' => $ancestor->getCpEditUrl()
            ];
        }

        $variables['showPreviewBtn'] = false;

        // Enable Live Preview?
        if (!$this->request->isMobileBrowser(true) && Craft::$app->getCategories()->isGroupTemplateValid($variables['group'], $category->siteId)) {
            $this->getView()->registerJs('Craft.LivePreview.init(' . Json::encode([
                    'fields' => '#title-field, #fields > div > div > .field',
                    'extraFields' => '#settings',
                    'previewUrl' => $category->getUrl(),
                    'previewAction' => Craft::$app->getSecurity()->hashData('categories/preview-category'),
                    'previewParams' => [
                        'groupId' => $variables['group']->id,
                        'categoryId' => $category->id,
                        'siteId' => $category->siteId,
                    ]
                ]) . ');');

            if (!Craft::$app->getConfig()->getGeneral()->headlessMode) {
                $variables['showPreviewBtn'] = true;
            }

            // Should we show the Share button too?
            if ($category->id !== null) {
                // If the category is enabled, use its main URL as its share URL.
                if ($category->getStatus() === Element::STATUS_ENABLED) {
                    $variables['shareUrl'] = $category->getUrl();
                } else {
                    $variables['shareUrl'] = UrlHelper::actionUrl('categories/share-category',
                        [
                            'categoryId' => $category->id,
                            'siteId' => $category->siteId
                        ]);
                }
            }
        }

        // Set the base CP edit URL
        $variables['baseCpEditUrl'] = "categories/{$variables['group']->handle}/{id}-{slug}";

        // Set the "Continue Editing" URL
        $siteSegment = Craft::$app->getIsMultiSite() && Craft::$app->getSites()->getCurrentSite()->id != $site->id ? "/{$site->handle}" : '';
        $variables['continueEditingUrl'] = $variables['baseCpEditUrl'] . $siteSegment;

        // Set the "Save and add another" URL
        $variables['nextCategoryUrl'] = "categories/{$variables['group']->handle}/new{$siteSegment}?parentId={parent.id}#";

        // Render the template!
        return $this->renderTemplate('categories/_edit', $variables);
    }

    /**
     * Previews a category.
     *
     * @return Response
     */
    public function actionPreviewCategory(): Response
    {
        $this->requirePostRequest();

        $category = $this->_getCategoryModel();
        $this->_enforceEditCategoryPermissions($category);
        $this->_populateCategoryModel($category);

        // Fire a 'previewCategory' event
        if ($this->hasEventHandlers(self::EVENT_PREVIEW_CATEGORY)) {
            $this->trigger(self::EVENT_PREVIEW_CATEGORY, new ElementEvent([
                'element' => $category,
            ]));
        }

        return $this->_showCategory($category);
    }

    /**
     * Saves an category.
     *
     * @return Response|null
     * @throws ServerErrorHttpException
     */
    public function actionSaveCategory()
    {
        $this->requirePostRequest();

        $category = $this->_getCategoryModel();
        $categoryVariable = $this->request->getValidatedBodyParam('categoryVariable') ?? 'category';

        // Permission enforcement
        $this->_enforceEditCategoryPermissions($category);

        // Are we duplicating the category?
        if ($this->request->getBodyParam('duplicate')) {
            // Swap $category with the duplicate
            try {
                $category = Craft::$app->getElements()->duplicateElement($category);
            } catch (InvalidElementException $e) {
                /** @var Category $clone */
                $clone = $e->element;

                if ($this->request->getAcceptsJson()) {
                    return $this->asJson([
                        'success' => false,
                        'errors' => $clone->getErrors(),
                    ]);
                }

                $this->setFailFlash(Craft::t('app', 'Couldn’t duplicate category.'));

                // Send the original category back to the template, with any validation errors on the clone
                $category->addErrors($clone->getErrors());
                Craft::$app->getUrlManager()->setRouteParams([
                    'category' => $category
                ]);

                return null;
            } catch (\Throwable $e) {
                throw new ServerErrorHttpException(Craft::t('app', 'An error occurred when duplicating the category.'), 0, $e);
            }
        }

        // Populate the category with post data
        $this->_populateCategoryModel($category);

        // Save the category
        if ($category->enabled && $category->getEnabledForSite()) {
            $category->setScenario(Element::SCENARIO_LIVE);
        }

        if (!Craft::$app->getElements()->saveElement($category)) {
            if ($this->request->getAcceptsJson()) {
                return $this->asJson([
                    'success' => false,
                    'errors' => $category->getErrors(),
                ]);
            }

            $this->setFailFlash(Craft::t('app', 'Couldn’t save category.'));

            // Send the category back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                $categoryVariable => $category
            ]);

            return null;
        }

        if ($this->request->getAcceptsJson()) {
            return $this->asJson([
                'success' => true,
                'id' => $category->id,
                'title' => $category->title,
                'slug' => $category->slug,
                'status' => $category->getStatus(),
                'url' => $category->getUrl(),
                'cpEditUrl' => $category->getCpEditUrl()
            ]);
        }

        $this->setSuccessFlash(Craft::t('app', 'Category saved.'));
        return $this->redirectToPostedUrl($category);
    }

    /**
     * Deletes a category.
     *
     * @return Response|null
     * @throws NotFoundHttpException if the requested category cannot be found
     */
    public function actionDeleteCategory()
    {
        $this->requirePostRequest();

        $categoryId = $this->request->getRequiredBodyParam('categoryId');
        $category = Craft::$app->getCategories()->getCategoryById($categoryId);

        if (!$category) {
            throw new NotFoundHttpException('Category not found');
        }

        // Make sure they have permission to do this
        $this->requirePermission('editCategories:' . $category->getGroup()->uid);

        // Delete it
        if (!Craft::$app->getElements()->deleteElement($category)) {
            if ($this->request->getAcceptsJson()) {
                return $this->asJson(['success' => false]);
            }

            $this->setFailFlash(Craft::t('app', 'Couldn’t delete category.'));

            // Send the category back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'category' => $category
            ]);

            return null;
        }

        if ($this->request->getAcceptsJson()) {
            return $this->asJson(['success' => true]);
        }

        $this->setSuccessFlash(Craft::t('app', 'Category deleted.'));
        return $this->redirectToPostedUrl($category);
    }

    /**
     * Redirects the client to a URL for viewing a disabled category on the front end.
     *
     * @param int $categoryId
     * @param int|null $siteId
     * @return Response
     * @throws Exception
     * @throws NotFoundHttpException if the requested category cannot be found
     * @throws ServerErrorHttpException if the category group is not configured properly
     */
    public function actionShareCategory(int $categoryId, int $siteId = null): Response
    {
        $category = Craft::$app->getCategories()->getCategoryById($categoryId, $siteId);

        if (!$category) {
            throw new NotFoundHttpException('Category not found');
        }

        // Make sure they have permission to be viewing this category
        $this->_enforceEditCategoryPermissions($category);

        // Make sure the category actually can be viewed
        if (!Craft::$app->getCategories()->isGroupTemplateValid($category->getGroup(), $category->siteId)) {
            throw new ServerErrorHttpException('Category group not configured properly');
        }

        // Create the token and redirect to the category URL with the token in place
        $token = Craft::$app->getTokens()->createToken([
            'categories/view-shared-category',
            [
                'categoryId' => $categoryId,
                'siteId' => $category->siteId
            ]
        ]);

        if ($token === false) {
            throw new Exception('There was a problem generating the token.');
        }

        $url = UrlHelper::urlWithToken($category->getUrl(), $token);

        return $this->response->redirect($url);
    }

    /**
     * Shows an category/draft/version based on a token.
     *
     * @param int $categoryId
     * @param int|null $siteId
     * @return Response
     * @throws NotFoundHttpException if the requested category cannot be found
     */
    public function actionViewSharedCategory(int $categoryId, int $siteId = null): Response
    {
        $this->requireToken();

        $category = Craft::$app->getCategories()->getCategoryById($categoryId, $siteId);

        if (!$category) {
            throw new NotFoundHttpException('Category not found');
        }

        return $this->_showCategory($category);
    }

    /**
     * Preps category category variables.
     *
     * @param array &$variables
     * @throws NotFoundHttpException if the requested category group or category cannot be found
     * @throws ForbiddenHttpException if the user is not permitted to edit content in the requested site
     */
    private function _prepEditCategoryVariables(array &$variables)
    {
        // Get the category group
        // ---------------------------------------------------------------------

        if (!empty($variables['groupHandle'])) {
            $variables['group'] = Craft::$app->getCategories()->getGroupByHandle($variables['groupHandle']);
        } else if (!empty($variables['groupId'])) {
            $variables['group'] = Craft::$app->getCategories()->getGroupById($variables['groupId']);
        }

        if (empty($variables['group'])) {
            throw new NotFoundHttpException('Category group not found');
        }

        // Get the site
        // ---------------------------------------------------------------------

        if (Craft::$app->getIsMultiSite()) {
            // Only use the sites that the user has access to
            $variables['siteIds'] = Craft::$app->getSites()->getEditableSiteIds();
        } else {
            /** @noinspection PhpUnhandledExceptionInspection */
            $variables['siteIds'] = [Craft::$app->getSites()->getPrimarySite()->id];
        }

        if (!$variables['siteIds']) {
            throw new ForbiddenHttpException('User not permitted to edit content in any sites');
        }

        if (empty($variables['site'])) {
            /** @noinspection PhpUnhandledExceptionInspection */
            $variables['site'] = Craft::$app->getSites()->getCurrentSite();

            if (!in_array($variables['site']->id, $variables['siteIds'], false)) {
                $variables['site'] = Craft::$app->getSites()->getSiteById($variables['siteIds'][0]);
            }

            $site = $variables['site'];
        } else {
            // Make sure they were requesting a valid site
            /** @var Site $site */
            $site = $variables['site'];
            if (!in_array($site->id, $variables['siteIds'], false)) {
                throw new ForbiddenHttpException('User not permitted to edit content in this site');
            }
        }

        // Get the category
        // ---------------------------------------------------------------------

        if (empty($variables['category'])) {
            if (!empty($variables['categoryId'])) {
                $variables['category'] = Craft::$app->getCategories()->getCategoryById($variables['categoryId'], $site->id);

                if (!$variables['category']) {
                    throw new NotFoundHttpException('Category not found');
                }
            } else {
                $variables['category'] = new Category();
                $variables['category']->groupId = $variables['group']->id;
                $variables['category']->enabled = true;
                $variables['category']->siteId = $site->id;
            }
        }

        // Prep the form tabs & content
        $form = $variables['group']->getFieldLayout()->createForm($variables['category']);
        $variables['tabs'] = $form->getTabMenu();
        $variables['fieldsHtml'] = $form->render();
    }

    /**
     * Fetches or creates a Category.
     *
     * @return Category
     * @throws BadRequestHttpException if the requested category group doesn't exist
     * @throws NotFoundHttpException if the requested category cannot be found
     */
    private function _getCategoryModel(): Category
    {
        $categoryId = $this->request->getBodyParam('categoryId');
        $siteId = $this->request->getBodyParam('siteId');

        if ($categoryId) {
            $category = Craft::$app->getCategories()->getCategoryById($categoryId, $siteId);

            if (!$category) {
                throw new NotFoundHttpException('Category not found');
            }
        } else {
            $groupId = $this->request->getRequiredBodyParam('groupId');
            if (($group = Craft::$app->getCategories()->getGroupById($groupId)) === null) {
                throw new BadRequestHttpException('Invalid category group ID: ' . $groupId);
            }

            $category = new Category();
            $category->groupId = $group->id;
            $category->fieldLayoutId = $group->fieldLayoutId;

            if ($siteId) {
                $category->siteId = $siteId;
            }
        }

        return $category;
    }

    /**
     * Enforces all Edit Category permissions.
     *
     * @param Category $category
     */
    private function _enforceEditCategoryPermissions(Category $category)
    {
        if (Craft::$app->getIsMultiSite()) {
            // Make sure they have access to this site
            $this->requirePermission('editSite:' . $category->getSite()->uid);
        }

        // Make sure the user is allowed to edit categories in this group
        $this->requirePermission('editCategories:' . $category->getGroup()->uid);
    }

    /**
     * Populates an Category with post data.
     *
     * @param Category $category
     */
    private function _populateCategoryModel(Category $category)
    {
        // Set the category attributes, defaulting to the existing values for whatever is missing from the post data
        $category->slug = $this->request->getBodyParam('slug', $category->slug);
        $category->enabled = (bool)$this->request->getBodyParam('enabled', $category->enabled);

        $category->title = $this->request->getBodyParam('title', $category->title);

        $fieldsLocation = $this->request->getParam('fieldsLocation', 'fields');
        $category->setFieldValuesFromRequest($fieldsLocation);

        // Parent
        if (($parentId = $this->request->getBodyParam('parentId')) !== null) {
            if (is_array($parentId)) {
                $parentId = reset($parentId) ?: '';
            }

            $category->newParentId = $parentId ?: '';
        }
    }

    /**
     * Displays a category.
     *
     * @param Category $category
     * @return Response
     * @throws ServerErrorHttpException if the category doesn't have a URL for the site it's configured with, or if the category's site ID is invalid
     */
    private function _showCategory(Category $category): Response
    {
        $categoryGroupSiteSettings = $category->getGroup()->getSiteSettings();

        if (!isset($categoryGroupSiteSettings[$category->siteId]) || !$categoryGroupSiteSettings[$category->siteId]->hasUrls) {
            throw new ServerErrorHttpException('The category ' . $category->id . ' doesn’t have a URL for the site ' . $category->siteId . '.');
        }

        $site = Craft::$app->getSites()->getSiteById($category->siteId, true);

        if (!$site) {
            throw new ServerErrorHttpException('Invalid site ID: ' . $category->siteId);
        }

        Craft::$app->language = $site->language;
        Craft::$app->set('locale', Craft::$app->getI18n()->getLocaleById($site->language));

        // Have this category override any freshly queried categories with the same ID/site
        if ($category->id) {
            Craft::$app->getElements()->setPlaceholderElement($category);
        }

        $this->getView()->getTwig()->disableStrictVariables();

        return $this->renderTemplate($categoryGroupSiteSettings[$category->siteId]->template, [
            'category' => $category
        ]);
    }
}
