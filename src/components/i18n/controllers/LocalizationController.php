<?php
namespace Blocks;

/**
 * Handles localization actions.
 */
class LocalizationController extends BaseController
{
	/**
	 * Adds a new a locale.
	 */
	public function actionAddLocale()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$locale = blx()->request->getRequiredPost('id');
		$success = blx()->i18n->addSiteLocale($locale);
		$this->returnJson(array('success' => $success));
	}

	/**
	 * Saves the new locale order.
	 */
	public function actionReorderLocales()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$locales = JsonHelper::decode(blx()->request->getRequiredPost('ids'));
		$success = blx()->i18n->reorderSiteLocales($locales);
		$this->returnJson(array('success' => $success));
	}

	/**
	 * Deletes a locale.
	 */
	public function actionDeleteLocale()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$locale = blx()->request->getRequiredPost('id');
		$success = blx()->i18n->deleteSiteLocale($locale);
		$this->returnJson(array('success' => $success));
	}

	/**
	 * Saves the language settings.
	 */
	public function actionSave()
	{
		$this->requirePostRequest();

		$languages = blx()->request->getPost('languages', array());
		sort($languages);

		if (blx()->systemSettings->saveSettings('languages', $languages))
		{
			blx()->userSession->setNotice(Blocks::t('Language settings saved.'));
			$this->redirectToPostedUrl();
		}
		else
		{
			blx()->userSession->setError(Blocks::t('Couldn’t save language settings.'));
		}

		$this->renderRequestedTemplate(array(
			'selectedLanguages' => $languages
		));
	}
}
