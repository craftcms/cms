<?php
namespace Craft;

/**
 * Handles section management tasks
 */
class SectionsController extends BaseController
{
	/**
	 * Init
	 */
	public function init()
	{
		// All section actions require an admin
		craft()->userSession->requireAdmin();
	}

	/**
	 * Saves a section
	 */
	public function actionSaveSection()
	{
		$this->requirePostRequest();

		$section = new SectionModel();

		// Set the simple stuff
		$section->id         = craft()->request->getPost('sectionId');
		$section->name       = craft()->request->getPost('name');
		$section->handle     = craft()->request->getPost('handle');
		$section->titleLabel = craft()->request->getPost('titleLabel');
		$section->hasUrls    = (bool)craft()->request->getPost('hasUrls');
		$section->template   = craft()->request->getPost('template');

		// Set the locales and URL formats
		$locales = array();
		$urlFormats = craft()->request->getPost('urlFormat');

		if (Craft::hasPackage(CraftPackage::Localize))
		{
			$localeIds = craft()->request->getPost('locales');
		}
		else
		{
			$primaryLocaleId = craft()->i18n->getPrimarySiteLocaleId();
			$localeIds = array($primaryLocaleId);
		}

		foreach ($localeIds as $localeId)
		{
			$locales[$localeId] = SectionLocaleModel::populateModel(array(
				'locale'    => $localeId,
				'urlFormat' => (isset($urlFormats[$localeId]) ? $urlFormats[$localeId] : null),
			));
		}

		$section->setLocales($locales);

		// Set the field layout
		$fieldLayout = craft()->fields->assembleLayoutFromPost();
		$fieldLayout->type = ElementType::Entry;
		$section->setFieldLayout($fieldLayout);

		// Save it
		if (craft()->sections->saveSection($section))
		{
			craft()->userSession->setNotice(Craft::t('Section saved.'));

			// TODO: Remove for 2.0
			if (isset($_POST['redirect']) && strpos($_POST['redirect'], '{sectionId}') !== false)
			{
				Craft::log('The {sectionId} token within the ‘redirect’ param on sections/saveSection requests has been deprecated. Use {id} instead.', LogLevel::Warning);
				$_POST['redirect'] = str_replace('{sectionId}', '{id}', $_POST['redirect']);
			}

			$this->redirectToPostedUrl($section);
		}
		else
		{
			craft()->userSession->setError(Craft::t('Couldn’t save section.'));
		}

		// Send the section back to the template
		craft()->urlManager->setRouteVariables(array(
			'section' => $section
		));
	}

	/**
	 * Deletes a section.
	 */
	public function actionDeleteSection()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$sectionId = craft()->request->getRequiredPost('id');

		craft()->sections->deleteSectionById($sectionId);
		$this->returnJson(array('success' => true));
	}
}
