<?php
namespace Blocks;

/**
 * Handles language settings from the control panel.
 */
class LanguageSettingsController extends BaseController
{
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
			blx()->user->setNotice(Blocks::t('Language settings saved.'));
			$this->redirectToPostedUrl();
		}
		else
		{
			blx()->user->setError(Blocks::t('Couldn’t save language settings.'));
		}

		$this->renderRequestedTemplate(array(
			'selectedLanguages' => $languages
		));
	}
}
