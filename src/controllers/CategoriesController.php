<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\controllers;

use Craft;
use craft\app\base\Element;
use craft\app\errors\Exception;
use craft\app\errors\HttpException;
use craft\app\helpers\JsonHelper;
use craft\app\helpers\UrlHelper;
use craft\app\elements\Category;
use craft\app\models\CategoryGroup;
use craft\app\models\CategoryGroupLocale as CategoryGroupLocaleModel;
use craft\app\web\Controller;

/**
 * The CategoriesController class is a controller that handles various actions related to categories and category
 * groups, such as creating, editing and deleting them.
 *
 * Note that all actions in the controller require an authenticated Craft session via [[Controller::allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
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
	 * @return null
	 */
	public function actionGroupIndex()
	{
		$this->requireAdmin();

		$groups = Craft::$app->categories->getAllGroups();

		$this->renderTemplate('settings/categories/index', [
			'categoryGroups' => $groups
		]);
	}

	/**
	 * Edit a category group.
	 *
	 * @param int           $groupId       The category group’s ID, if editing an existing group.
	 * @param CategoryGroup $categoryGroup The category group being edited, if there were any validation errors.
	 *
	 * @throws HttpException
	 * @return null
	 */
	public function actionEditCategoryGroup($groupId = null, CategoryGroup $categoryGroup = null)
	{
		$this->requireAdmin();

		// Breadcrumbs
		$variables['crumbs'] = [
			['label' => Craft::t('app', 'Settings'), 'url' => UrlHelper::getUrl('settings')],
			['label' => Craft::t('app', 'Categories'),  'url' => UrlHelper::getUrl('settings/categories')]
		];

		$variables['brandNewGroup'] = false;

		if ($groupId !== null)
		{
			if ($categoryGroup === null)
			{
				$categoryGroup = Craft::$app->categories->getGroupById($groupId);

				if (!$categoryGroup)
				{
					throw new HttpException(404);
				}
			}

			$variables['title'] = $categoryGroup->name;
		}
		else
		{
			if ($categoryGroup === null)
			{
				$categoryGroup = new CategoryGroup();
				$variables['brandNewGroup'] = true;
			}

			$variables['title'] = Craft::t('app', 'Create a new category group');
		}

		$variables['tabs'] = [
			'settings'    => ['label' => Craft::t('app', 'Settings'), 'url' => '#categorygroup-settings'],
			'fieldLayout' => ['label' => Craft::t('app', 'Field Layout'), 'url' => '#categorygroup-fieldlayout']
		];

		$variables['groupId'] = $groupId;
		$variables['categoryGroup'] = $categoryGroup;

		$this->renderTemplate('settings/categories/_edit', $variables);
	}

	/**
	 * Save a category group.
	 *
	 * @return null
	 */
	public function actionSaveGroup()
	{
		$this->requirePostRequest();
		$this->requireAdmin();

		$group = new CategoryGroup();

		// Set the simple stuff
		$group->id        = Craft::$app->getRequest()->getBodyParam('groupId');
		$group->name      = Craft::$app->getRequest()->getBodyParam('name');
		$group->handle    = Craft::$app->getRequest()->getBodyParam('handle');
		$group->hasUrls   = Craft::$app->getRequest()->getBodyParam('hasUrls');
		$group->template  = Craft::$app->getRequest()->getBodyParam('template');
		$group->maxLevels = Craft::$app->getRequest()->getBodyParam('maxLevels');

		// Locale-specific URL formats
		$locales = [];

		foreach (Craft::$app->getI18n()->getSiteLocaleIds() as $localeId)
		{
			$locales[$localeId] = new CategoryGroupLocaleModel([
				'locale'          => $localeId,
				'urlFormat'       => Craft::$app->getRequest()->getBodyParam('urlFormat.'.$localeId),
				'nestedUrlFormat' => Craft::$app->getRequest()->getBodyParam('nestedUrlFormat.'.$localeId),
			]);
		}

		$group->setLocales($locales);

		// Group the field layout
		$fieldLayout = Craft::$app->fields->assembleLayoutFromPost();
		$fieldLayout->type = Category::className();
		$group->setFieldLayout($fieldLayout);

		// Save it
		if (Craft::$app->categories->saveGroup($group))
		{
			Craft::$app->getSession()->setNotice(Craft::t('app', 'Category group saved.'));
			$this->redirectToPostedUrl($group);
		}
		else
		{
			Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t save the category group.'));
		}

		// Send the category group back to the template
		Craft::$app->getUrlManager()->setRouteParams([
			'categoryGroup' => $group
		]);
	}

	/**
	 * Deletes a category group.
	 *
	 * @return null
	 */
	public function actionDeleteCategoryGroup()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();
		$this->requireAdmin();

		$groupId = Craft::$app->getRequest()->getRequiredBodyParam('id');

		Craft::$app->categories->deleteGroupById($groupId);
		$this->returnJson(['success' => true]);
	}

	// Categories
	// -------------------------------------------------------------------------

	/**
	 * Displays the category index page.
	 *
	 * @param string $groupHandle The category group’s handle.
	 *
	 * @throws HttpException
	 * @return null
	 */
	public function actionCategoryIndex($groupHandle = null)
	{
		$groups = Craft::$app->categories->getEditableGroups();

		if (!$groups)
		{
			throw new HttpException(404);
		}

		$this->renderTemplate('categories/_index', [
			'groupHandle' => $groupHandle,
			'groups' => $groups
		]);
	}

	/**
	 * Displays the category edit page.
	 *
	 * @param string   $groupHandle The category group’s handle.
	 * @param int      $categoryId  The category’s ID, if editing an existing category.
	 * @param int      $localeId    The locale ID, if specified.
	 * @param Category $category    The category being edited, if there were any validation errors.
	 *
	 * @throws HttpException
	 * @return null
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

		$this->_enforceEditCategoryPermissions($variables['category']);

		// Parent Category selector variables
		// ---------------------------------------------------------------------

		if ($variables['group']->maxLevels != 1)
		{
			$variables['elementClass'] = Category::className();

			// Define the parent options criteria
			$variables['parentOptionCriteria'] = [
				'locale'        => $variables['localeId'],
				'groupId'       => $variables['group']->id,
				'status'        => null,
				'localeEnabled' => null,
			];

			if ($variables['group']->maxLevels)
			{
				$variables['parentOptionCriteria']['level'] = '< '.$variables['group']->maxLevels;
			}

			if ($variables['category']->id)
			{
				// Prevent the current category, or any of its descendants, from being options
				$excludeIds = Category::find()
					->descendantOf($variables['category'])
					->status(null)
					->localeEnabled(false)
					->ids();

				$excludeIds[] = $variables['category']->id;
				$variables['parentOptionCriteria']['where'] = ['not in', 'elements.id', $excludeIds];
			}

			// Get the initially selected parent
			$parentId = Craft::$app->getRequest()->getParam('parentId');

			if ($parentId === null && $variables['category']->id)
			{
				$parentIds = $variables['category']->getAncestors(1)->status(null)->localeEnabled(null)->ids();

				if ($parentIds)
				{
					$parentId = $parentIds[0];
				}
			}

			if ($parentId)
			{
				$variables['parent'] = Craft::$app->categories->getCategoryById($parentId, $variables['localeId']);
			}
		}

		// Other variables
		// ---------------------------------------------------------------------

		// Page title
		if (!$variables['category']->id)
		{
			$variables['title'] = Craft::t('app', 'Create a new category');
		}
		else
		{
			$variables['docTitle'] = Craft::t('app', $variables['category']->title);
			$variables['title'] = Craft::t('app', $variables['category']->title);
		}

		// Breadcrumbs
		$variables['crumbs'] = [
			['label' => Craft::t('app', 'Categories'), 'url' => UrlHelper::getUrl('categories')],
			['label' => Craft::t('app', $variables['group']->name), 'url' => UrlHelper::getUrl('categories/'.$variables['group']->handle)]
		];

		foreach ($variables['category']->getAncestors() as $ancestor)
		{
			$variables['crumbs'][] = ['label' => $ancestor->title, 'url' => $ancestor->getCpEditUrl()];
		}

		// Enable Live Preview?
		if (!Craft::$app->getRequest()->getIsMobileBrowser(true) && Craft::$app->categories->isGroupTemplateValid($variables['group']))
		{
			Craft::$app->templates->includeJs('Craft.LivePreview.init('.JsonHelper::encode([
				'fields'        => '#title-field, #fields > div > div > .field',
				'extraFields'   => '#settings',
				'previewUrl'    => $variables['category']->getUrl(),
				'previewAction' => 'categories/previewCategory',
				'previewParams' => [
				                       'groupId'    => $variables['group']->id,
				                       'categoryId' => $variables['category']->id,
				                       'locale'     => $variables['category']->locale,
				]
				]).');');

			$variables['showPreviewBtn'] = true;

			// Should we show the Share button too?
			if ($variables['category']->id)
			{
				// If the category is enabled, use its main URL as its share URL.
				if ($variables['category']->getStatus() == Element::ENABLED)
				{
					$variables['shareUrl'] = $variables['category']->getUrl();
				}
				else
				{
					$variables['shareUrl'] = UrlHelper::getActionUrl('categories/shareCategory', [
						'categoryId' => $variables['category']->id,
						'locale'     => $variables['category']->locale
					]);
				}
			}
		}
		else
		{
			$variables['showPreviewBtn'] = false;
		}

		// Set the base CP edit URL
		$variables['baseCpEditUrl'] = 'categories/'.$variables['group']->handle.'/{id}-{slug}';

		// Set the "Continue Editing" URL
		$variables['continueEditingUrl'] = $variables['baseCpEditUrl'] .
			(Craft::$app->isLocalized() && Craft::$app->getLanguage() != $variables['localeId'] ? '/'.$variables['localeId'] : '');

		// Render the template!
		Craft::$app->templates->includeCssResource('css/category.css');
		$this->renderTemplate('categories/_edit', $variables);
	}

	/**
	 * Previews a category.
	 *
	 * @throws HttpException
	 * @return null
	 */
	public function actionPreviewCategory()
	{
		$this->requirePostRequest();

		$category = $this->_getCategoryModel();
		$this->_enforceEditCategoryPermissions($category);
		$this->_populateCategoryModel($category);

		$this->_showCategory($category);
	}

	/**
	 * Saves an category.
	 *
	 * @return null
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
		if (Craft::$app->categories->saveCategory($category))
		{
			if (Craft::$app->getRequest()->getIsAjax())
			{
				$return['success']   = true;
				$return['title']     = $category->title;
				$return['cpEditUrl'] = $category->getCpEditUrl();

				$this->returnJson([
					'success'   => true,
					'id'        => $category->id,
					'title'     => $category->title,
					'status'    => $category->getStatus(),
					'url'       => $category->getUrl(),
					'cpEditUrl' => $category->getCpEditUrl()
				]);
			}
			else
			{
				Craft::$app->getSession()->setNotice(Craft::t('app', 'Category saved.'));
				$this->redirectToPostedUrl($category);
			}
		}
		else
		{
			if (Craft::$app->getRequest()->getIsAjax())
			{
				$this->returnJson([
					'success' => false,
					'errors'  => $category->getErrors(),
				]);
			}
			else
			{
				Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t save category.'));

				// Send the category back to the template
				Craft::$app->getUrlManager()->setRouteParams([
					'category' => $category
				]);
			}
		}
	}

	/**
	 * Deletes a category.
	 *
	 * @throws Exception
	 * @return null
	 */
	public function actionDeleteCategory()
	{
		$this->requirePostRequest();

		$categoryId = Craft::$app->getRequest()->getRequiredBodyParam('categoryId');
		$category = Craft::$app->categories->getCategoryById($categoryId);

		if (!$category)
		{
			throw new Exception(Craft::t('app', 'No category exists with the ID “{id}”.', ['id' => $categoryId]));
		}

		// Make sure they have permission to do this
		$this->requirePermission('editCategories:'.$category->groupId);

		// Delete it
		if (Craft::$app->categories->deleteCategory($category))
		{
			if (Craft::$app->getRequest()->getIsAjax())
			{
				$this->returnJson(['success' => true]);
			}
			else
			{
				Craft::$app->getSession()->setNotice(Craft::t('app', 'Category deleted.'));
				$this->redirectToPostedUrl($category);
			}
		}
		else
		{
			if (Craft::$app->getRequest()->getIsAjax())
			{
				$this->returnJson(['success' => false]);
			}
			else
			{
				Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t delete category.'));

				// Send the category back to the template
				Craft::$app->getUrlManager()->setRouteParams([
					'category' => $category
				]);
			}
		}
	}

	/**
	 * Redirects the client to a URL for viewing a disabled category on the front end.
	 *
	 * @param mixed $categoryId
	 * @param mixed $locale
	 *
	 * @throws HttpException
	 * @return null
	 */
	public function actionShareCategory($categoryId, $locale = null)
	{
		$category = Craft::$app->categories->getCategoryById($categoryId, $locale);

		if (!$category)
		{
			throw new HttpException(404);
		}

		// Make sure they have permission to be viewing this category
		$this->_enforceEditCategoryPermissions($category);

		// Make sure the category actually can be viewed
		if (!Craft::$app->categories->isGroupTemplateValid($category->getGroup()))
		{
			throw new HttpException(404);
		}

		// Create the token and redirect to the category URL with the token in place
		$token = Craft::$app->tokens->createToken([
			'action' => 'categories/viewSharedCategory',
			'params' => ['categoryId' => $categoryId, 'locale' => $category->locale]
		]);

		$url = UrlHelper::getUrlWithToken($category->getUrl(), $token);
		Craft::$app->getResponse()->redirect($url);
	}

	/**
	 * Shows an category/draft/version based on a token.
	 *
	 * @param mixed $categoryId
	 * @param mixed $locale
	 *
	 * @throws HttpException
	 * @return null
	 */
	public function actionViewSharedCategory($categoryId, $locale = null)
	{
		$this->requireToken();

		$category = Craft::$app->categories->getCategoryById($categoryId, $locale);

		if (!$category)
		{
			throw new HttpException(404);
		}

		$this->_showCategory($category);
	}

	// Private Methods
	// =========================================================================

	/**
	 * Preps category category variables.
	 *
	 * @param array &$variables
	 *
	 * @throws HttpException|Exception
	 * @return null
	 */
	private function _prepEditCategoryVariables(&$variables)
	{
		// Get the category group
		// ---------------------------------------------------------------------

		if (!empty($variables['groupHandle']))
		{
			$variables['group'] = Craft::$app->categories->getGroupByHandle($variables['groupHandle']);
		}
		else if (!empty($variables['groupId']))
		{
			$variables['group'] = Craft::$app->categories->getGroupById($variables['groupId']);
		}

		if (empty($variables['group']))
		{
			throw new HttpException(404);
		}

		// Get the locale
		// ---------------------------------------------------------------------

		$variables['localeIds'] = Craft::$app->getI18n()->getEditableLocaleIds();

		if (!$variables['localeIds'])
		{
			throw new HttpException(403, Craft::t('app', 'Your account doesn’t have permission to edit any of this site’s locales.'));
		}

		if (empty($variables['localeId']))
		{
			$variables['localeId'] = Craft::$app->language;

			if (!in_array($variables['localeId'], $variables['localeIds']))
			{
				$variables['localeId'] = $variables['localeIds'][0];
			}
		}
		else
		{
			// Make sure they were requesting a valid locale
			if (!in_array($variables['localeId'], $variables['localeIds']))
			{
				throw new HttpException(404);
			}
		}

		// Get the category
		// ---------------------------------------------------------------------

		if (empty($variables['category']))
		{
			if (!empty($variables['categoryId']))
			{
				$variables['category'] = Craft::$app->categories->getCategoryById($variables['categoryId'], $variables['localeId']);

				if (!$variables['category'])
				{
					throw new HttpException(404);
				}
			}
			else
			{
				$variables['category'] = new Category();
				$variables['category']->groupId = $variables['group']->id;
				$variables['category']->enabled = true;

				if (!empty($variables['localeId']))
				{
					$variables['category']->locale = $variables['localeId'];
				}
			}
		}

		// Define the content tabs
		// ---------------------------------------------------------------------

		$variables['tabs'] = [];

		foreach ($variables['group']->getFieldLayout()->getTabs() as $index => $tab)
		{
			// Do any of the fields on this tab have errors?
			$hasErrors = false;

			if ($variables['category']->hasErrors())
			{
				foreach ($tab->getFields() as $field)
				{
					if ($variables['category']->getErrors($field->getField()->handle))
					{
						$hasErrors = true;
						break;
					}
				}
			}

			$variables['tabs'][] = [
				'label' => Craft::t('app', $tab->name),
				'url'   => '#tab'.($index+1),
				'class' => ($hasErrors ? 'error' : null)
			];
		}
	}

	/**
	 * Fetches or creates a Category.
	 *
	 * @throws Exception
	 * @return Category
	 */
	private function _getCategoryModel()
	{
		$categoryId = Craft::$app->getRequest()->getBodyParam('categoryId');
		$localeId = Craft::$app->getRequest()->getBodyParam('locale');

		if ($categoryId)
		{
			$category = Craft::$app->categories->getCategoryById($categoryId, $localeId);

			if (!$category)
			{
				throw new Exception(Craft::t('app', 'No category exists with the ID “{id}”.', ['id' => $categoryId]));
			}
		}
		else
		{
			$category = new Category();
			$category->groupId = Craft::$app->getRequest()->getRequiredBodyParam('groupId');

			if ($localeId)
			{
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
	 * @return null
	 */
	private function _enforceEditCategoryPermissions(Category $category)
	{
		if (Craft::$app->isLocalized())
		{
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
	 * @return null
	 */
	private function _populateCategoryModel(Category $category)
	{
		// Set the category attributes, defaulting to the existing values for whatever is missing from the post data
		$category->slug    = Craft::$app->getRequest()->getBodyParam('slug', $category->slug);
		$category->enabled = (bool) Craft::$app->getRequest()->getBodyParam('enabled', $category->enabled);

		$category->getContent()->title = Craft::$app->getRequest()->getBodyParam('title', $category->title);

		$fieldsLocation = Craft::$app->getRequest()->getParam('fieldsLocation', 'fields');
		$category->setContentFromPost($fieldsLocation);

		// Parent
		$parentId = Craft::$app->getRequest()->getBodyParam('parentId');

		if (is_array($parentId))
		{
			$parentId = isset($parentId[0]) ? $parentId[0] : null;
		}

		$category->newParentId = $parentId;
	}

	/**
	 * Displays a category.
	 *
	 * @param Category $category
	 *
	 * @throws HttpException
	 * @return null
	 */
	private function _showCategory(Category $category)
	{
		$group = $category->getGroup();

		if (!$group)
		{
			Craft::error('Attempting to preview a category that doesn’t have a group.', __METHOD__);
			throw new HttpException(404);
		}

		Craft::$app->setLanguage($category->locale);

		// Have this category override any freshly queried categories with the same ID/locale
		Craft::$app->elements->setPlaceholderElement($category);

		Craft::$app->templates->getTwig()->disableStrictVariables();

		$this->renderTemplate($group->template, [
			'category' => $category
		]);
	}
}
