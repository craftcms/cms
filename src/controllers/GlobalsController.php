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

			$this->redirectToPostedUrl(array(
				'setId' => $globalSet->id
			));
		}
		else
		{
			craft()->userSession->setError(Craft::t('Couldn’t save global set.'));
		}

		// Reload the original template
		$this->renderRequestedTemplate(array(
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

		craft()->elements->deleteElementById($globalSetId);
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

		$fields = craft()->request->getPost('fields', array());
		$globalSet->setContent($fields);

		if (craft()->globals->saveContent($globalSet))
		{
			craft()->userSession->setNotice(Craft::t('Globals saved.'));
			$this->redirectToPostedUrl();
		}
		else
		{
			craft()->userSession->setError(Craft::t('Couldn’t save globals.'));
		}

		$this->renderRequestedTemplate(array(
			'globalSet' => $globalSet,
		));
	}
}
