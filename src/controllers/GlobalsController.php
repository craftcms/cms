<?php
namespace Craft;

/**
 * Handles global set management tasks
 */
class GlobalsController extends BaseController
{
	/**
	 * Saves a global set.
	 */
	public function actionSaveSet()
	{
		$this->requirePostRequest();

		$globalSet = new GlobalSetModel();

		// Set the simple stuff
		$globalSet->id     = craft()->request->getPost('setId');
		$globalSet->name   = craft()->request->getPost('name');
		$globalSet->handle = craft()->request->getPost('handle');

		// Set the field layout
		$fieldLayout = craft()->fields->assembleLayoutFromPost(false);
		$fieldLayout->type = ElementType::GlobalSet;
		$globalSet->setFieldLayout($fieldLayout);

		// Save it
		if (craft()->globals->saveSet($globalSet))
		{
			craft()->userSession->setNotice(Craft::t('Global set saved.'));

			// TODO: Remove for 2.0
			if (isset($_POST['redirect']) && mb_strpos($_POST['redirect'], '{setId}') !== false)
			{
				craft()->deprecator->deprecate('setid_token_saveset', 'The {setId} token within the ‘redirect’ param on globals/saveSet requests has been deprecated. Use {id} instead.', '1.1');
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
	 */
	public function actionDeleteSet()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$globalSetId = craft()->request->getRequiredPost('id');

		craft()->globals->deleteSetById($globalSetId);
		$this->returnJson(array('success' => true));
	}

	/**
	 * Saves a global set's content.
	 */
	public function actionSaveContent()
	{
		$this->requirePostRequest();

		$globalSetId = craft()->request->getRequiredPost('setId');
		$globalSet = craft()->globals->getSetById($globalSetId);

		if (!$globalSet)
		{
			throw new Exception(Craft::t('No global set exists with the ID “{id}”.', array('id' => $globalSetId)));
		}

		$globalSet->locale = craft()->request->getPost('locale', craft()->i18n->getPrimarySiteLocaleId());

		$fields = craft()->request->getPost('fields');
		$globalSet->getContent()->setAttributes($fields);

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
