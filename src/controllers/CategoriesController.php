<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\controllers;

use Craft;
use craft\app\base\Element;
use craft\app\helpers\Json;
use craft\app\helpers\Url;
use craft\app\elements\Category;
use craft\app\models\CategoryGroup;
use craft\app\models\CategoryGroupLocale;
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

        $group = new CategoryGroup();

        // Set the simple stuff
        $group->id = Craft::$app->getRequest()->getBodyParam('groupId');
        $group->name = Craft::$app->getRequest()->getBodyParam('name');
        $group->handle = Craft::$app->getRequest()->getBodyParam('handle');
        $group->hasUrls = (bool)Craft::$app->getRequest()->getBodyParam('hasUrls');
        $group->template = Craft::$app->getRequest()->getBodyParam('template');
        $group->maxLevels = Craft::$app->getRequest()->getBodyParam('maxLevels');

        // Locale-specific URL formats
        $locales = [];

        foreach (Craft::$app->getI18n()->getSiteLocaleIds() as $localeId) {
            $locales[$localeId] = new CategoryGroupLocale([
                'locale' => $localeId,
                'urlFormat' => Craft::$app->getRequest()->getBodyParam('urlFormat.'.$localeId),
                'nestedUrlFormat' => Craft::$app->getRequest()->getBodyParam('nestedUrlFormat.'.$localeId),
            ]);
        }

        $group->setLocales($locales);

        // Group the field layout
        $fieldLayout = Craft::$app->getFields()->assembleLayoutFromPost();
        $fieldLayout->type = Category::className();
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
        $this->requireAjaxRequest();
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
     * @param integer  $localeId    The locale ID, if specified.
     * @param Category $category    The category being edited, if there were any validation errors.
     *
     * @return string The rendering result
     */
    public function actionEditCategory($groupHandle, $categoryId = null, $localeId = null, Category $category = null)
    {
        $variables = [
            'groupHandle' => $groupHandle,
            'categoryId' => $categoryId,
            'localeId' => $localeId,
            'category' => $category
        ];

        $this->_prepEditCategoryVariables($variables);

        /** @var Category $category */
        $category = $variables['category'];

        $this->_enforceEditCategoryPermissions($category);

        // Parent Category selector variables
        // ---------------------------------------------------------------------

        if ($variables['group']->maxLevels != 1) {
            $variables['elementType'] = Category::className();

            // Define the parent options criteria
            $variables['parentOptionCriteria'] = [
                'locale' => $variables['localeId'],
                'groupId' => $variables['group']->id,
                'status' => null,
                'localeEnabled' => null,
            ];

            if ($variables['group']->maxLevels) {
                $variables['parentOptionCriteria']['level'] = '< '.$variables['group']->maxLevels;
            }

            if ($category->id) {
                // Prevent the current category, or any of its descendants, from being options
                $excludeIds = Category::find()
                    ->descendantOf($category)
                    ->status(null)
                    ->localeEnabled(false)
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
                $parentIds = $category->getAncestors(1)->status(null)->localeEnabled(false)->ids();

                if ($parentIds) {
                    $parentId = $parentIds[0];
                }
            }

            if ($parentId) {
                $variables['parent'] = Craft::$app->getCategories()->getCategoryById($parentId, $variables['localeId']);
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
        if (!Craft::$app->getRequest()->getIsMobileBrowser(true) && Craft::$app->getCategories()->isGroupTemplateValid($variables['group'])) {
            Craft::$app->getView()->registerJs('Craft.LivePreview.init('.Json::encode([
                    'fields' => '#title-field, #fields > div > div > .field',
                    'extraFields' => '#settings',
                    'previewUrl' => $category->getUrl(),
                    'previewAction' => 'categories/preview-category',
                    'previewParams' => [
                        'groupId' => $variables['group']->id,
                        'categoryId' => $category->id,
                        'locale' => $category->locale,
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
                            'locale' => $category->locale
                        ]);
                }
            }
        } else {
            $variables['showPreviewBtn'] = false;
        }

        // Set the base CP edit URL
        $variables['baseCpEditUrl'] = 'categories/'.$variables['group']->handle.'/{id}-{slug}';

        // Set the "Continue Editing" URL
        $variables['continueEditingUrl'] = $variables['baseCpEditUrl'].
            (Craft::$app->isLocalized() && Craft::$app->language != $variables['localeId'] ? '/'.$variables['localeId'] : '');

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
            if (Craft::$app->getRequest()->getIsAjax()) {
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
            if (Craft::$app->getRequest()->getIsAjax()) {
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
            if (Craft::$app->getRequest()->getIsAjax()) {
                return $this->asJson(['success' => true]);
            }

            Craft::$app->getSession()->setNotice(Craft::t('app', 'Category deleted.'));

            return $this->redirectToPostedUrl($category);
        }

        if (Craft::$app->getRequest()->getIsAjax()) {
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
     * @param mixed $categoryId
     * @param mixed $locale
     *
     * @return Response
     * @throws NotFoundHttpException if the requested category cannot be found
     * @throws ServerErrorHttpException if the category group is not configured properly
     */
    public function actionShareCategory($categoryId, $locale = null)
    {
        $category = Craft::$app->getCategories()->getCategoryById($categoryId, $locale);

        if (!$category) {
            throw new NotFoundHttpException('Category not found');
        }

        // Make sure they have permission to be viewing this category
        $this->_enforceEditCategoryPermissions($category);

        // Make sure the category actually can be viewed
        if (!Craft::$app->getCategories()->isGroupTemplateValid($category->getGroup())) {
            throw new ServerErrorHttpException('Category group not configured properly');
        }

        // Create the token and redirect to the category URL with the token in place
        $token = Craft::$app->getTokens()->createToken([
            'action' => 'categories/view-shared-category',
            'params' => [
                'categoryId' => $categoryId,
                'locale' => $category->locale
            ]
        ]);

        $url = Url::getUrlWithToken($category->getUrl(), $token);

        return Craft::$app->getResponse()->redirect($url);
    }

    /**
     * Shows an category/draft/version based on a token.
     *
     * @param mixed $categoryId
     * @param mixed $locale
     *
     * @return string
     * @throws NotFoundHttpException if the requested category cannot be found
     */
    public function actionViewSharedCategory($categoryId, $locale = null)
    {
        $this->requireToken();

        $category = Craft::$app->getCategories()->getCategoryById($categoryId, $locale);

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
     * @throws ForbiddenHttpException if the user is not permitted to edit content in the requested locale
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

        // Get the locale
        // ---------------------------------------------------------------------

        $variables['localeIds'] = Craft::$app->getI18n()->getEditableLocaleIds();

        if (!$variables['localeIds']) {
            throw new ForbiddenHttpException('User not permitted to edit content in any locales');
        }

        if (empty($variables['localeId'])) {
            $variables['localeId'] = Craft::$app->language;

            if (!in_array($variables['localeId'], $variables['localeIds'])) {
                $variables['localeId'] = $variables['localeIds'][0];
            }
        } else {
            // Make sure they were requesting a valid locale
            if (!in_array($variables['localeId'], $variables['localeIds'])) {
                throw new ForbiddenHttpException('User not permitted to edit content in this locale');
            }
        }

        // Get the category
        // ---------------------------------------------------------------------

        if (empty($variables['category'])) {
            if (!empty($variables['categoryId'])) {
                $variables['category'] = Craft::$app->getCategories()->getCategoryById($variables['categoryId'], $variables['localeId']);

                if (!$variables['category']) {
                    throw new NotFoundHttpException('Category not found');
                }
            } else {
                $variables['category'] = new Category();
                $variables['category']->groupId = $variables['group']->id;
                $variables['category']->enabled = true;

                if (!empty($variables['localeId'])) {
                    $variables['category']->locale = $variables['localeId'];
                }
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
        $localeId = Craft::$app->getRequest()->getBodyParam('locale');

        if ($categoryId) {
            $category = Craft::$app->getCategories()->getCategoryById($categoryId, $localeId);

            if (!$category) {
                throw new NotFoundHttpException('Category not found');
            }
        } else {
            $category = new Category();
            $category->groupId = Craft::$app->getRequest()->getRequiredBodyParam('groupId');

            if ($localeId) {
                $category->locale = $localeId;
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
        if (Craft::$app->isLocalized()) {
            // Make sure they have access to this locale
            $this->requirePermission('editLocale:'.$category->locale);
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
     * @throws ServerErrorHttpException if the category is missing its category group
     */
    private function _showCategory(Category $category)
    {
        $group = $category->getGroup();

        if (!$group) {
            throw new ServerErrorHttpException('Category is missing its category group');
        }

        Craft::$app->language = $category->locale;

        // Have this category override any freshly queried categories with the same ID/locale
        Craft::$app->getElements()->setPlaceholderElement($category);

        Craft::$app->getView()->getTwig()->disableStrictVariables();

        return $this->renderTemplate($group->template, [
            'category' => $category
        ]);
    }
}
