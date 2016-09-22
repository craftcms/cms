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
 * The CategoriesController class is a controller that handles various actions related to categories and category
 * groups, such as creating, editing and deleting them.
 *
 * Note that all actions in the controller require an authenticated Craft session via [[Controller::allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class CategoriesController extends Controller
{
    // Public Methods
    // =========================================================================

    // Category Groups
    // -------------------------------------------------------------------------

    /**
     * Category groups index.
     *
     * @return string The rendering result
     */
    public function actionGroupIndex()
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
     * @param integer       $groupId       The category group’s ID, if editing an existing group.
     * @param CategoryGroup $categoryGroup The category group being edited, if there were any validation errors.
     *
     * @return string The rendering result
     * @throws NotFoundHttpException if the requested category group cannot be found
     */
    public function actionEditCategoryGroup($groupId = null, CategoryGroup $categoryGroup = null)
    {
        $this->requireAdmin();

        // Breadcrumbs
        $variables['crumbs'] = [
            [
                'label' => Craft::t('app', 'Settings'),
                'url' => Url::getUrl('settings')
            ],
            [
                'label' => Craft::t('app', 'Categories'),
                'url' => Url::getUrl('settings/categories')
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

            $variables['title'] = $categoryGroup->name;
        } else {
            if ($categoryGroup === null) {
                $categoryGroup = new CategoryGroup();
                $variables['brandNewGroup'] = true;
            }

            $variables['title'] = Craft::t('app', 'Create a new category group');
        }

        $variables['tabs'] = [
            'settings' => [
                'label' => Craft::t('app', 'Settings'),
                'url' => '#categorygroup-settings'
            ],
            'fieldLayout' => [
                'label' => Craft::t('app', 'Field Layout'),
                'url' => '#categorygroup-fieldlayout'
            ]
        ];

        $variables['groupId'] = $groupId;
        $variables['categoryGroup'] = $categoryGroup;

        return $this->renderTemplate('settings/categories/_edit', $variables);
    }

    /**
     * Save a category group.
     *
     * @return Response|null
     */
    public function actionSaveGroup()
    {
        $this->requirePostRequest();
        $this->requireAdmin();

        $request = Craft::$app->getRequest();

        $group = new CategoryGroup();

        // Main group settings
        $group->id = $request->getBodyParam('groupId');
        $group->name = $request->getBodyParam('name');
        $group->handle = $request->getBodyParam('handle');
        $group->maxLevels = $request->getBodyParam('maxLevels');

        // Site-specific settings
        $allSiteSettings = [];

        foreach (Craft::$app->getSites()->getAllSites() as $site) {
            $postedSettings = $request->getBodyParam('sites.'.$site->handle);

            $siteSettings = new CategoryGroup_SiteSettings();
            $siteSettings->siteId = $site->id;
            $siteSettings->hasUrls = !empty($postedSettings['uriFormat']);

            if ($siteSettings->hasUrls) {
                $siteSettings->uriFormat = $postedSettings['uriFormat'];
                $siteSettings->template = $postedSettings['template'];
            } else {
                $siteSettings->uriFormat = null;
                $siteSettings->template = null;
            }

            $allSiteSettings[$site->id] = $siteSettings;
        }

        $group->setSiteSettings($allSiteSettings);

        // Group the field layout
        $fieldLayout = Craft::$app->getFields()->assembleLayoutFromPost();
        $fieldLayout->type = Category::class;
        $group->setFieldLayout($fieldLayout);

        // Save it
        if (Craft::$app->getCategories()->saveGroup($group)) {
            Craft::$app->getSession()->setNotice(Craft::t('app', 'Category group saved.'));

            return $this->redirectToPostedUrl($group);
        }

        Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t save the category group.'));


        // Send the category group back to the template
        Craft::$app->getUrlManager()->setRouteParams([
            'categoryGroup' => $group
        ]);

        return null;
    }

    /**
     * Deletes a category group.
     *
     * @return Response
     */
    public function actionDeleteCategoryGroup()
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();
        $this->requireAdmin();

        $groupId = Craft::$app->getRequest()->getRequiredBodyParam('id');

        Craft::$app->getCategories()->deleteGroupById($groupId);

        return $this->asJson(['success' => true]);
    }

    // Categories
    // -------------------------------------------------------------------------

    /**
     * Displays the category index page.
     *
     * @param string $groupHandle The category group’s handle.
     *
     * @return string The rendering result
     * @throws ForbiddenHttpException if the user is not permitted to edit categories
     */
    public function actionCategoryIndex($groupHandle = null)
    {
        $groups = Craft::$app->getCategories()->getEditableGroups();

        if (!$groups) {
            throw new ForbiddenHttpException('User not permitted to edit categories');
        }

        return $this->renderTemplate('categories/_index', [
            'groupHandle' => $groupHandle,
            'groups' => $groups
        ]);
    }

    /**
     * Displays the category edit page.
     *
     * @param string   $groupHandle The category group’s handle.
     * @param integer  $categoryId  The category’s ID, if editing an existing category.
     * @param string   $siteHandle  The site handle, if specified.
     * @param Category $category    The category being edited, if there were any validation errors.
     *
     * @return string The rendering result
     * @throws NotFoundHttpException if the requested site handle is invalid
     */
    public function actionEditCategory($groupHandle, $categoryId = null, $siteHandle = null, Category $category = null)
    {
        if ($siteHandle) {
            $site = Craft::$app->getSites()->getSiteByHandle($siteHandle);

            if (!$site) {
                throw new NotFoundHttpException('Invalid site handle: '.$siteHandle);
            }
        } else {
            $site = Craft::$app->getSites()->currentSite;
        }

        $variables = [
            'groupHandle' => $groupHandle,
            'categoryId' => $categoryId,
            'site' => $site,
            'category' => $category
        ];

        $this->_prepEditCategoryVariables($variables);

        /** @var Category $category */
        $category = $variables['category'];

        $this->_enforceEditCategoryPermissions($category);

        // Parent Category selector variables
        // ---------------------------------------------------------------------

        if ($variables['group']->maxLevels != 1) {
            $variables['elementType'] = Category::class;

            // Define the parent options criteria
            $variables['parentOptionCriteria'] = [
                'siteId' => $site->id,
                'groupId' => $variables['group']->id,
                'status' => null,
                'enabledForSite' => false,
            ];

            if ($variables['group']->maxLevels) {
                $variables['parentOptionCriteria']['level'] = '< '.$variables['group']->maxLevels;
            }

            if ($category->id) {
                // Prevent the current category, or any of its descendants, from being options
                $excludeIds = Category::find()
                    ->descendantOf($category)
                    ->status(null)
                    ->enabledForSite(false)
                    ->ids();

                $excludeIds[] = $category->id;
                $variables['parentOptionCriteria']['where'] = [
                    'not in',
                    'elements.id',
                    $excludeIds
                ];
            }

            // Get the initially selected parent
            $parentId = Craft::$app->getRequest()->getParam('parentId');

            if ($parentId === null && $category->id) {
                $parentIds = $category->getAncestors(1)->status(null)->enabledForSite(false)->ids();

                if ($parentIds) {
                    $parentId = $parentIds[0];
                }
            }

            if ($parentId) {
                $variables['parent'] = Craft::$app->getCategories()->getCategoryById($parentId, $site->id);
            }
        }

        // Other variables
        // ---------------------------------------------------------------------

        // Page title
        if (!$category->id) {
            $variables['title'] = Craft::t('app', 'Create a new category');
        } else {
            $variables['docTitle'] = $variables['title'] = $category->title;
        }

        // Breadcrumbs
        $variables['crumbs'] = [
            [
                'label' => Craft::t('app', 'Categories'),
                'url' => Url::getUrl('categories')
            ],
            [
                'label' => Craft::t('site', $variables['group']->name),
                'url' => Url::getUrl('categories/'.$variables['group']->handle)
            ]
        ];

        /** @var Category $ancestor */
        foreach ($category->getAncestors() as $ancestor) {
            $variables['crumbs'][] = [
                'label' => $ancestor->title,
                'url' => $ancestor->getCpEditUrl()
            ];
        }

        // Enable Live Preview?
        if (!Craft::$app->getRequest()->isMobileBrowser(true) && Craft::$app->getCategories()->isGroupTemplateValid($variables['group'], $category->siteId)) {
            Craft::$app->getView()->registerJs('Craft.LivePreview.init('.Json::encode([
                    'fields' => '#title-field, #fields > div > div > .field',
                    'extraFields' => '#settings',
                    'previewUrl' => $category->getUrl(),
                    'previewAction' => 'categories/preview-category',
                    'previewParams' => [
                        'groupId' => $variables['group']->id,
                        'categoryId' => $category->id,
                        'siteId' => $category->siteId,
                    ]
                ]).');');

            $variables['showPreviewBtn'] = true;

            // Should we show the Share button too?
            if ($category->id) {
                // If the category is enabled, use its main URL as its share URL.
                if ($category->getStatus() == Element::STATUS_ENABLED) {
                    $variables['shareUrl'] = $category->getUrl();
                } else {
                    $variables['shareUrl'] = Url::getActionUrl('categories/share-category',
                        [
                            'categoryId' => $category->id,
                            'siteId' => $category->siteId
                        ]);
                }
            }
        } else {
            $variables['showPreviewBtn'] = false;
        }

        // Set the base CP edit URL
        $variables['baseCpEditUrl'] = 'categories/'.$variables['group']->handle.'/{id}-{slug}';

        // Set the "Continue Editing" URL
        $variables['continueEditingUrl'] = $variables['baseCpEditUrl'];

        if (Craft::$app->getIsMultiSite() && Craft::$app->getSites()->currentSite->id != $site->id) {
            $variables['continueEditingUrl'] .= '/'.$site->handle;
        };

        // Render the template!
        Craft::$app->getView()->registerCssResource('css/category.css');

        return $this->renderTemplate('categories/_edit', $variables);
    }

    /**
     * Previews a category.
     *
     * @return string
     */
    public function actionPreviewCategory()
    {
        $this->requirePostRequest();

        $category = $this->_getCategoryModel();
        $this->_enforceEditCategoryPermissions($category);
        $this->_populateCategoryModel($category);

        return $this->_showCategory($category);
    }

    /**
     * Saves an category.
     *
     * @return Response|null
     */
    public function actionSaveCategory()
    {
        $this->requirePostRequest();

        $category = $this->_getCategoryModel();

        // Permission enforcement
        $this->_enforceEditCategoryPermissions($category);

        // Populate the category with post data
        $this->_populateCategoryModel($category);

        // Save the category
        if (Craft::$app->getCategories()->saveCategory($category)) {
            if (Craft::$app->getRequest()->getAcceptsJson()) {
                $return['success'] = true;
                $return['title'] = $category->title;
                $return['cpEditUrl'] = $category->getCpEditUrl();

                return $this->asJson([
                    'success' => true,
                    'id' => $category->id,
                    'title' => $category->title,
                    'status' => $category->getStatus(),
                    'url' => $category->getUrl(),
                    'cpEditUrl' => $category->getCpEditUrl()
                ]);
            } else {
                Craft::$app->getSession()->setNotice(Craft::t('app', 'Category saved.'));

                return $this->redirectToPostedUrl($category);
            }
        } else {
            if (Craft::$app->getRequest()->getAcceptsJson()) {
                return $this->asJson([
                    'success' => false,
                    'errors' => $category->getErrors(),
                ]);
            } else {
                Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t save category.'));

                // Send the category back to the template
                Craft::$app->getUrlManager()->setRouteParams([
                    'category' => $category
                ]);
            }
        }

        return null;
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

        $categoryId = Craft::$app->getRequest()->getRequiredBodyParam('categoryId');
        $category = Craft::$app->getCategories()->getCategoryById($categoryId);

        if (!$category) {
            throw new NotFoundHttpException('Category not found');
        }

        // Make sure they have permission to do this
        $this->requirePermission('editCategories:'.$category->groupId);

        // Delete it
        if (Craft::$app->getCategories()->deleteCategory($category)) {
            if (Craft::$app->getRequest()->getAcceptsJson()) {
                return $this->asJson(['success' => true]);
            }

            Craft::$app->getSession()->setNotice(Craft::t('app', 'Category deleted.'));

            return $this->redirectToPostedUrl($category);
        }

        if (Craft::$app->getRequest()->getAcceptsJson()) {
            return $this->asJson(['success' => false]);
        }

        Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t delete category.'));

        // Send the category back to the template
        Craft::$app->getUrlManager()->setRouteParams([
            'category' => $category
        ]);


        return null;
    }

    /**
     * Redirects the client to a URL for viewing a disabled category on the front end.
     *
     * @param integer $categoryId
     * @param integer $siteId
     *
     * @return Response
     * @throws NotFoundHttpException if the requested category cannot be found
     * @throws ServerErrorHttpException if the category group is not configured properly
     */
    public function actionShareCategory($categoryId, $siteId = null)
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

        $url = Url::getUrlWithToken($category->getUrl(), $token);

        return Craft::$app->getResponse()->redirect($url);
    }

    /**
     * Shows an category/draft/version based on a token.
     *
     * @param integer $categoryId
     * @param integer $siteId
     *
     * @return string
     * @throws NotFoundHttpException if the requested category cannot be found
     */
    public function actionViewSharedCategory($categoryId, $siteId = null)
    {
        $this->requireToken();

        $category = Craft::$app->getCategories()->getCategoryById($categoryId, $siteId);

        if (!$category) {
            throw new NotFoundHttpException('Category not found');
        }

        return $this->_showCategory($category);
    }

    // Private Methods
    // =========================================================================

    /**
     * Preps category category variables.
     *
     * @param array &$variables
     *
     * @return void
     * @throws NotFoundHttpException if the requested category group or category cannot be found
     * @throws ForbiddenHttpException if the user is not permitted to edit content in the requested site
     */
    private function _prepEditCategoryVariables(&$variables)
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

        $variables['siteIds'] = Craft::$app->getSites()->getEditableSiteIds();

        if (!$variables['siteIds']) {
            throw new ForbiddenHttpException('User not permitted to edit content in any sites');
        }

        if (empty($variables['site'])) {
            $variables['site'] = Craft::$app->getSites()->currentSite;

            if (!in_array($variables['site']->id, $variables['siteIds'])) {
                $variables['site'] = Craft::$app->getSites()->getSiteById($variables['siteIds'][0]);
            }

            $site = $variables['site'];
        } else {
            // Make sure they were requesting a valid site
            /** @var Site $site */
            $site = $variables['site'];
            if (!in_array($site->id, $variables['siteIds'])) {
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

        // Define the content tabs
        // ---------------------------------------------------------------------

        $variables['tabs'] = [];

        foreach ($variables['group']->getFieldLayout()->getTabs() as $index => $tab) {
            // Do any of the fields on this tab have errors?
            $hasErrors = false;

            if ($variables['category']->hasErrors()) {
                foreach ($tab->getFields() as $field) {
                    /** @var Field $field */
                    if ($variables['category']->getErrors($field->handle)) {
                        $hasErrors = true;
                        break;
                    }
                }
            }

            $variables['tabs'][] = [
                'label' => Craft::t('site', $tab->name),
                'url' => '#tab'.($index + 1),
                'class' => ($hasErrors ? 'error' : null)
            ];
        }
    }

    /**
     * Fetches or creates a Category.
     *
     * @return Category
     * @throws NotFoundHttpException if the requested category cannot be found
     */
    private function _getCategoryModel()
    {
        $categoryId = Craft::$app->getRequest()->getBodyParam('categoryId');
        $siteId = Craft::$app->getRequest()->getBodyParam('siteId');

        if ($categoryId) {
            $category = Craft::$app->getCategories()->getCategoryById($categoryId, $siteId);

            if (!$category) {
                throw new NotFoundHttpException('Category not found');
            }
        } else {
            $category = new Category();
            $category->groupId = Craft::$app->getRequest()->getRequiredBodyParam('groupId');

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
     *
     * @return void
     */
    private function _enforceEditCategoryPermissions(Category $category)
    {
        if (Craft::$app->getIsMultiSite()) {
            // Make sure they have access to this site
            $this->requirePermission('editSite:'.$category->siteId);
        }

        // Make sure the user is allowed to edit categories in this group
        $this->requirePermission('editCategories:'.$category->groupId);
    }

    /**
     * Populates an Category with post data.
     *
     * @param Category $category
     *
     * @return void
     */
    private function _populateCategoryModel(Category $category)
    {
        // Set the category attributes, defaulting to the existing values for whatever is missing from the post data
        $category->slug = Craft::$app->getRequest()->getBodyParam('slug', $category->slug);
        $category->enabled = (bool)Craft::$app->getRequest()->getBodyParam('enabled', $category->enabled);

        $category->title = Craft::$app->getRequest()->getBodyParam('title', $category->title);

        $fieldsLocation = Craft::$app->getRequest()->getParam('fieldsLocation', 'fields');
        $category->setFieldValuesFromPost($fieldsLocation);

        // Parent
        $parentId = Craft::$app->getRequest()->getBodyParam('parentId');

        if (is_array($parentId)) {
            $parentId = isset($parentId[0]) ? $parentId[0] : null;
        }

        $category->newParentId = $parentId;
    }

    /**
     * Displays a category.
     *
     * @param Category $category
     *
     * @return string The rendering result
     * @throws ServerErrorHttpException if the category doesn't have a URL for the site it's configured with, or if the category's site ID is invalid
     */
    private function _showCategory(Category $category)
    {
        $categoryGroupSiteSettings = $category->getGroup()->getSiteSettings();

        if (!isset($categoryGroupSiteSettings[$category->siteId]) || !$categoryGroupSiteSettings[$category->siteId]->hasUrls) {
            throw new ServerErrorHttpException('The category '.$category->id.' doesn\'t have a URL for the site '.$category->siteId.'.');
        }

        $site = Craft::$app->getSites()->getSiteById($category->siteId);

        if (!$site) {
            throw new ServerErrorHttpException('Invalid site ID: '.$category->siteId);
        }

        Craft::$app->language = $site->language;

        // Have this category override any freshly queried categories with the same ID/site
        Craft::$app->getElements()->setPlaceholderElement($category);

        Craft::$app->getView()->getTwig()->disableStrictVariables();

        return $this->renderTemplate($categoryGroupSiteSettings[$category->siteId]->template, [
            'category' => $category
        ]);
    }
}
