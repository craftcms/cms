<?php
namespace Blocks;

/**
 * Handles section management tasks
 */
class SectionsController extends BaseController
{
	/**
	 * Saves a section
	 */
	public function actionSaveSection()
	{
		$this->requirePostRequest();

		$section = new SectionModel();

		// Set the simple stuff
		$section->id         = blx()->request->getPost('sectionId');
		$section->name       = blx()->request->getPost('name');
		$section->handle     = blx()->request->getPost('handle');
		$section->titleLabel = blx()->request->getPost('titleLabel');
		$section->hasUrls    = (bool)blx()->request->getPost('hasUrls');
		$section->template   = blx()->request->getPost('template');

		// Set the locales and URL formats
		$locales = array();
		$urlFormats = blx()->request->getPost('urlFormat');

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
			$locales[$localeId] = SectionLocaleModel::populateModel(array(
				'locale'    => $localeId,
				'urlFormat' => (isset($urlFormats[$localeId]) ? $urlFormats[$localeId] : null),
			));
		}

		$section->setLocales($locales);

		// Set the field layout
		$fieldLayout = blx()->fields->assembleLayoutFromPost();
		$fieldLayout->type = ElementType::Entry;
		$section->setFieldLayout($fieldLayout);

		// Save it
		if (blx()->sections->saveSection($section))
		{
			blx()->userSession->setNotice(Blocks::t('Section saved.'));

			$this->redirectToPostedUrl(array(
				'sectionId' => $section->id
			));
		}
		else
		{
			blx()->userSession->setError(Blocks::t('Couldn’t save section.'));
		}

		// Reload the original template
		$this->renderRequestedTemplate(array(
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

		$sectionId = blx()->request->getRequiredPost('id');

		blx()->sections->deleteSectionById($sectionId);
		$this->returnJson(array('success' => true));
	}
}
