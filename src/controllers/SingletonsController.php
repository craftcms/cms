<?php
namespace Blocks;

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
		$singleton->id       = blx()->request->getPost('singletonId');
		$singleton->name     = blx()->request->getPost('name');
		$singleton->template = blx()->request->getPost('template');

		// Set the locales and URL formats
		$locales = array();
		$uris = blx()->request->getPost('uri');

		if (Blocks::hasPackage(BlocksPackage::Language))
		{
			$localeIds = blx()->request->getPost('locales');
		}
		else
		{
			$primaryLocaleId = blx()->i18n->getPrimarySiteLocale()->getId();
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
		$fieldLayout = blx()->fields->assembleLayoutFromPost();
		$fieldLayout->type = ElementType::Singleton;
		$singleton->setFieldLayout($fieldLayout);

		// Save it
		if (blx()->singletons->saveSingleton($singleton))
		{
			blx()->userSession->setNotice(Blocks::t('Singleton saved.'));

			$this->redirectToPostedUrl(array(
				'singletonId' => $singleton->id
			));
		}
		else
		{
			blx()->userSession->setError(Blocks::t('Couldn’t save singleton.'));
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

		$singletonId = blx()->request->getRequiredPost('id');

		blx()->singletons->deleteSingletonById($singletonId);
		$this->returnJson(array('success' => true));
	}

	/**
	 * Saves a singleton's content.
	 */
	public function actionSaveContent()
	{
		$this->requirePostRequest();

		$singletonId = blx()->request->getRequiredPost('singletonId');
		$singleton = blx()->singletons->getSingletonById($singletonId);

		if (!$singleton)
		{
			throw new Exception(Blocks::t('No singleton exists with the ID “{id}”.', array('id' => $singletonId)));
		}

		$fields = blx()->request->getPost('fields', array());
		$singleton->setContent($fields);

		if (blx()->singletons->saveContent($singleton))
		{
			blx()->userSession->setNotice(Blocks::t('Singleton saved.'));
			$this->redirectToPostedUrl();
		}
		else
		{
			blx()->userSession->setError(Blocks::t('Couldn’t save singleton.'));
		}

		$this->renderRequestedTemplate(array(
			'singleton' => $singleton,
		));
	}
}
