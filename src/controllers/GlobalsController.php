<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\controllers;

use craft\app\Craft;
use craft\app\enums\ElementType;
use craft\app\errors\Exception;
use craft\app\errors\HttpException;
use craft\app\models\GlobalSet      as GlobalSetModel;

/**
 * The GlobalsController class is a controller that handles various global and global set related tasks such as saving,
 * deleting displaying both globals and global sets.
 *
 * Note that all actions in the controller require an authenticated Craft session via [[BaseController::allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class GlobalsController extends BaseController
{
	// Public Methods
	// =========================================================================

	/**
	 * Saves a global set.
	 *
	 * @return null
	 */
	public function actionSaveSet()
	{
		$this->requirePostRequest();
		$this->requireAdmin();

		$globalSet = new GlobalSetModel();

		// Set the simple stuff
		$globalSet->id     = Craft::$app->request->getPost('setId');
		$globalSet->name   = Craft::$app->request->getPost('name');
		$globalSet->handle = Craft::$app->request->getPost('handle');

		// Set the field layout
		$fieldLayout = Craft::$app->fields->assembleLayoutFromPost();
		$fieldLayout->type = ElementType::GlobalSet;
		$globalSet->setFieldLayout($fieldLayout);

		// Save it
		if (Craft::$app->globals->saveSet($globalSet))
		{
			Craft::$app->getSession()->setNotice(Craft::t('Global set saved.'));
			$this->redirectToPostedUrl($globalSet);
		}
		else
		{
			Craft::$app->getSession()->setError(Craft::t('Couldn’t save global set.'));
		}

		// Send the global set back to the template
		Craft::$app->urlManager->setRouteVariables([
			'globalSet' => $globalSet
		]);
	}

	/**
	 * Deletes a global set.
	 *
	 * @return null
	 */
	public function actionDeleteSet()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();
		$this->requireAdmin();

		$globalSetId = Craft::$app->request->getRequiredPost('id');

		Craft::$app->globals->deleteSetById($globalSetId);
		$this->returnJson(['success' => true]);
	}

	/**
	 * Edits a global set's content.
	 *
	 * @param array $variables
	 *
	 * @throws HttpException
	 * @return null
	 */
	public function actionEditContent(array $variables = [])
	{
		// Make sure a specific global set was requested
		if (empty($variables['globalSetHandle']))
		{
			throw new HttpException(400, Craft::t('Param “{name}” doesn’t exist.', ['name' => 'globalSetHandle']));
		}

		// Get the locales the user is allowed to edit
		$editableLocaleIds = Craft::$app->i18n->getEditableLocaleIds();

		// Editing a specific locale?
		if (isset($variables['localeId']))
		{
			// Make sure the user has permission to edit that locale
			if (!in_array($variables['localeId'], $editableLocaleIds))
			{
				throw new HttpException(404);
			}
		}
		else
		{
			// Are they allowed to edit the current app locale?
			if (in_array(Craft::$app->language, $editableLocaleIds))
			{
				$variables['localeId'] = Craft::$app->language;
			}
			else
			{
				// Just use the first locale they are allowed to edit
				$variables['localeId'] = $editableLocaleIds[0];
			}
		}

		// Get the global sets the user is allowed to edit, in the requested locale
		$variables['globalSets'] = [];

		$criteria = Craft::$app->elements->getCriteria(ElementType::GlobalSet);
		$criteria->locale = $variables['localeId'];
		$globalSets = $criteria->find();

		foreach ($globalSets as $globalSet)
		{
			if (Craft::$app->getUser()->checkPermission('editGlobalSet:'.$globalSet->id))
			{
				$variables['globalSets'][$globalSet->handle] = $globalSet;
			}
		}

		if (!$variables['globalSets'] || !isset($variables['globalSets'][$variables['globalSetHandle']]))
		{
			throw new HttpException(404);
		}

		if (!isset($variables['globalSet']))
		{
			$variables['globalSet'] = $variables['globalSets'][$variables['globalSetHandle']];
		}

		// Render the template!
		$this->renderTemplate('globals/_edit', $variables);
	}

	/**
	 * Saves a global set's content.
	 *
	 * @throws Exception
	 * @return null
	 */
	public function actionSaveContent()
	{
		$this->requirePostRequest();

		$globalSetId = Craft::$app->request->getRequiredPost('setId');
		$localeId = Craft::$app->request->getPost('locale', Craft::$app->i18n->getPrimarySiteLocaleId());

		// Make sure the user is allowed to edit this global set and locale
		$this->requirePermission('editGlobalSet:'.$globalSetId);

		if (Craft::$app->isLocalized())
		{
			$this->requirePermission('editLocale:'.$localeId);
		}

		$globalSet = Craft::$app->globals->getSetById($globalSetId, $localeId);

		if (!$globalSet)
		{
			throw new Exception(Craft::t('No global set exists with the ID “{id}”.', ['id' => $globalSetId]));
		}

		$globalSet->setContentFromPost('fields');

		if (Craft::$app->globals->saveContent($globalSet))
		{
			Craft::$app->getSession()->setNotice(Craft::t('Globals saved.'));
			$this->redirectToPostedUrl();
		}
		else
		{
			Craft::$app->getSession()->setError(Craft::t('Couldn’t save globals.'));
		}

		// Send the global set back to the template
		Craft::$app->urlManager->setRouteVariables([
			'globalSet' => $globalSet,
		]);
	}
}
