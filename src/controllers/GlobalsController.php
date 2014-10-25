<?php
namespace Craft;

/**
 * The GlobalsController class is a controller that handles various global and global set related tasks such as saving,
 * deleting displaying both globals and global sets.
 *
 * Note that all actions in the controller require an authenticated Craft session via {@link BaseController::allowAnonymous}.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.controllers
 * @since     1.0
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
		craft()->userSession->requireAdmin();

		$globalSet = new GlobalSetModel();

		// Set the simple stuff
		$globalSet->id     = craft()->request->getPost('setId');
		$globalSet->name   = craft()->request->getPost('name');
		$globalSet->handle = craft()->request->getPost('handle');

		// Set the field layout
		$fieldLayout = craft()->fields->assembleLayoutFromPost();
		$fieldLayout->type = ElementType::GlobalSet;
		$globalSet->setFieldLayout($fieldLayout);

		// Save it
		if (craft()->globals->saveSet($globalSet))
		{
			craft()->userSession->setNotice(Craft::t('Global set saved.'));

			if (isset($_POST['redirect']) && mb_strpos($_POST['redirect'], '{setId}') !== false)
			{
				craft()->deprecator->log('GlobalsController::saveSet():setId_redirect', 'The {setId} token within the ‘redirect’ param on globals/saveSet requests has been deprecated. Use {id} instead.');
				$_POST['redirect'] = str_replace('{setId}', '{id}', $_POST['redirect']);
			}

			$this->redirectToPostedUrl($globalSet);
		}
		else
		{
			craft()->userSession->setError(Craft::t('Couldn’t save global set.'));
		}

		// Send the global set back to the template
		craft()->urlManager->setRouteVariables(array(
			'globalSet' => $globalSet
		));
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
		craft()->userSession->requireAdmin();

		$globalSetId = craft()->request->getRequiredPost('id');

		craft()->globals->deleteSetById($globalSetId);
		$this->returnJson(array('success' => true));
	}

	/**
	 * Edits a global set's content.
	 *
	 * @param array $variables
	 *
	 * @throws HttpException
	 * @return null
	 */
	public function actionEditContent(array $variables = array())
	{
		// Make sure a specific global set was requested
		if (empty($variables['globalSetHandle']))
		{
			throw new HttpException(400, Craft::t('Param “{name}” doesn’t exist.', array('name' => 'globalSetHandle')));
		}

		// Get the locales the user is allowed to edit
		$editableLocaleIds = craft()->i18n->getEditableLocaleIds();

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
			if (in_array(craft()->language, $editableLocaleIds))
			{
				$variables['localeId'] = craft()->language;
			}
			else
			{
				// Just use the first locale they are allowed to edit
				$variables['localeId'] = $editableLocaleIds[0];
			}
		}

		// Get the global sets the user is allowed to edit, in the requested locale
		$variables['globalSets'] = array();

		$criteria = craft()->elements->getCriteria(ElementType::GlobalSet);
		$criteria->locale = $variables['localeId'];
		$globalSets = $criteria->find();

		foreach ($globalSets as $globalSet)
		{
			if (craft()->userSession->checkPermission('editGlobalSet:'.$globalSet->id))
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

		$globalSetId = craft()->request->getRequiredPost('setId');
		$localeId = craft()->request->getPost('locale', craft()->i18n->getPrimarySiteLocaleId());

		// Make sure the user is allowed to edit this global set and locale
		craft()->userSession->requirePermission('editGlobalSet:'.$globalSetId);

		if (craft()->isLocalized())
		{
			craft()->userSession->requirePermission('editLocale:'.$localeId);
		}

		$globalSet = craft()->globals->getSetById($globalSetId, $localeId);

		if (!$globalSet)
		{
			throw new Exception(Craft::t('No global set exists with the ID “{id}”.', array('id' => $globalSetId)));
		}

		$globalSet->setContentFromPost('fields');

		if (craft()->globals->saveContent($globalSet))
		{
			craft()->userSession->setNotice(Craft::t('Globals saved.'));
			$this->redirectToPostedUrl();
		}
		else
		{
			craft()->userSession->setError(Craft::t('Couldn’t save globals.'));
		}

		// Send the global set back to the template
		craft()->urlManager->setRouteVariables(array(
			'globalSet' => $globalSet,
		));
	}
}
