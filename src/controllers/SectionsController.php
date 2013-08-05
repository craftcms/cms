<?php
namespace Craft;

/**
 * Handles section management tasks
 */
class SectionsController extends BaseController
{
	/**
	 * Edit a section.
	 *
	 * @param array $variables
	 * @throws HttpException
	 */
	public function actionEditSection(array $variables = array())
	{
		craft()->userSession->requireAdmin();

		$variables['brandNewSection'] = false;

		if (empty($variables['section']))
		{
			if (!empty($variables['sectionId']))
			{
				$variables['section'] = craft()->sections->getSectionById($variables['sectionId']);

				if (!$variables['section'])
				{
					throw new HttpException(404);
				}

				$variables['title'] = $variables['section']->name;
			}
			else
			{
				$variables['section'] = new SectionModel();
				$variables['title'] = Craft::t('Create a new section');
				$variables['brandNewSection'] = true;
			}
		}

		$variables['crumbs'] = array(
			array('label' => Craft::t('Settings'), 'url' => UrlHelper::getUrl('settings')),
			array('label' => Craft::t('Sections'), 'url' => UrlHelper::getUrl('settings/sections')),
		);

		$variables['tabs'] = array(
			'settings'    => array('label' => Craft::t('Settings'),     'url' => '#section-settings'),
			'fieldlayout' => array('label' => Craft::t('Field Layout'), 'url' => '#section-fieldlayout'),
		);

		$this->renderTemplate('settings/sections/_edit', $variables);
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
