<?php
namespace Craft;

/**
 * Handles singleton management tasks
 */
class SingletonsController extends BaseController
{
	/**
	 * Saves a singleton
	 */
	public function actionSaveSingleton()
	{
		$this->requirePostRequest();

		$singleton = new SingletonModel();

		// Set the simple stuff
		$singleton->id       = craft()->request->getPost('singletonId');
		$singleton->name     = craft()->request->getPost('name');
		$singleton->template = craft()->request->getPost('template');

		// Set the locales and URL formats
		$locales = array();
		$uris = craft()->request->getPost('uri');

		if (Craft::hasPackage(CraftPackage::Language))
		{
			$localeIds = craft()->request->getPost('locales');
		}
		else
		{
			$primaryLocaleId = craft()->i18n->getPrimarySiteLocale()->getId();
			$localeIds = array($primaryLocaleId);
		}

		foreach ($localeIds as $localeId)
		{
			$locales[$localeId] = SingletonLocaleModel::populateModel(array(
				'locale' => $localeId,
			));

			$locales[$localeId]->setUri(isset($uris[$localeId]) ? $uris[$localeId] : '');
		}

		$singleton->setLocales($locales);

		// Set the field layout
		$fieldLayout = craft()->fields->assembleLayoutFromPost();
		$fieldLayout->type = ElementType::Singleton;
		$singleton->setFieldLayout($fieldLayout);

		// Save it
		if (craft()->singletons->saveSingleton($singleton))
		{
			craft()->userSession->setNotice(Craft::t('Singleton saved.'));

			$this->redirectToPostedUrl(array(
				'singletonId' => $singleton->id
			));
		}
		else
		{
			craft()->userSession->setError(Craft::t('Couldn’t save singleton.'));
		}

		// Reload the original template
		$this->renderRequestedTemplate(array(
			'singleton' => $singleton
		));
	}

	/**
	 * Deletes a singleton.
	 */
	public function actionDeleteSingleton()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$singletonId = craft()->request->getRequiredPost('id');

		craft()->singletons->deleteSingletonById($singletonId);
		$this->returnJson(array('success' => true));
	}

	/**
	 * Saves a singleton's content.
	 */
	public function actionSaveContent()
	{
		$this->requirePostRequest();

		$singletonId = craft()->request->getRequiredPost('singletonId');
		$singleton = craft()->singletons->getSingletonById($singletonId);

		if (!$singleton)
		{
			throw new Exception(Craft::t('No singleton exists with the ID “{id}”.', array('id' => $singletonId)));
		}

		$fields = craft()->request->getPost('fields', array());
		$singleton->setContent($fields);

		if (craft()->singletons->saveContent($singleton))
		{
			craft()->userSession->setNotice(Craft::t('Singleton saved.'));
			$this->redirectToPostedUrl();
		}
		else
		{
			craft()->userSession->setError(Craft::t('Couldn’t save singleton.'));
		}

		$this->renderRequestedTemplate(array(
			'singleton' => $singleton,
		));
	}
}
