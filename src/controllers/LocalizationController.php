<?php
namespace Craft;

Craft::requirePackage(CraftPackage::Language);

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

		$locale = craft()->request->getRequiredPost('id');
		$success = craft()->i18n->addSiteLocale($locale);
		$this->returnJson(array('success' => $success));
	}

	/**
	 * Saves the new locale order.
	 */
	public function actionReorderLocales()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$locales = JsonHelper::decode(craft()->request->getRequiredPost('ids'));
		$success = craft()->i18n->reorderSiteLocales($locales);
		$this->returnJson(array('success' => $success));
	}

	/**
	 * Deletes a locale.
	 */
	public function actionDeleteLocale()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$locale = craft()->request->getRequiredPost('id');
		$success = craft()->i18n->deleteSiteLocale($locale);
		$this->returnJson(array('success' => $success));
	}

	/**
	 * Saves the language settings.
	 */
	public function actionSave()
	{
		$this->requirePostRequest();

		$languages = craft()->request->getPost('languages', array());
		sort($languages);

		if (craft()->systemSettings->saveSettings('languages', $languages))
		{
			craft()->userSession->setNotice(Craft::t('Language settings saved.'));
			$this->redirectToPostedUrl();
		}
		else
		{
			craft()->userSession->setError(Craft::t('Couldn’t save language settings.'));
		}

		$this->renderRequestedTemplate(array(
			'selectedLanguages' => $languages
		));
	}
}
