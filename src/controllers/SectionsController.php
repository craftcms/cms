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
	 * Edit a section.
	 *
	 * @param array $variables
	 * @throws HttpException
	 */
	public function actionEditSection(array $variables = array())
	{
		$variables['brandNewSection'] = false;

		if (!empty($variables['sectionId']))
		{
			if (empty($variables['section']))
			{
				$variables['section'] = craft()->sections->getSectionById($variables['sectionId']);

				if (!$variables['section'])
				{
					throw new HttpException(404);
				}
			}

			$variables['title'] = $variables['section']->name;
		}
		else
		{
			if (empty($variables['section']))
			{
				$variables['section'] = new SectionModel();
				$variables['brandNewSection'] = true;
			}

			$variables['title'] = Craft::t('Create a new section');
		}

		$variables['crumbs'] = array(
			array('label' => Craft::t('Settings'), 'url' => UrlHelper::getUrl('settings')),
			array('label' => Craft::t('Sections'), 'url' => UrlHelper::getUrl('settings/sections')),
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

		// Save it
		if (craft()->sections->saveSection($section))
		{
			craft()->userSession->setNotice(Craft::t('Section saved.'));

			// TODO: Remove for 2.0
			if (isset($_POST['redirect']) && mb_strpos($_POST['redirect'], '{sectionId}') !== false)
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

	// Entry Types

	/**
	 * Entry types index
	 *
	 * @param array $variables
	 * @throws HttpException
	 */
	public function actionEntryTypesIndex(array $variables = array())
	{
		if (empty($variables['sectionId']))
		{
			throw new HttpException(400);
		}

		$variables['section'] = craft()->sections->getSectionById($variables['sectionId']);

		if (!$variables['section'])
		{
			throw new HttpException(404);
		}

		$variables['crumbs'] = array(
			array('label' => Craft::t('Settings'), 'url' => UrlHelper::getUrl('settings')),
			array('label' => Craft::t('Sections'), 'url' => UrlHelper::getUrl('settings/sections')),
			array('label' => $variables['section']->name, 'url' => UrlHelper::getUrl('settings/sections/'.$variables['section']->id)),
		);

		$variables['title'] = Craft::t('{section} Entry Types', array('section' => $variables['section']->name));

		$this->renderTemplate('settings/sections/_entrytypes/index', $variables);
	}

	/**
	 * Edit an entry type
	 *
	 * @param array $variables
	 * @throws HttpException
	 */
	public function actionEditEntryType(array $variables = array())
	{
		if (empty($variables['sectionId']))
		{
			throw new HttpException(400);
		}

		$variables['section'] = craft()->sections->getSectionById($variables['sectionId']);

		if (!$variables['section'])
		{
			throw new HttpException(404);
		}

		if (!empty($variables['entryTypeId']))
		{
			if (empty($variables['entryType']))
			{
				$variables['entryType'] = craft()->sections->getEntryTypeById($variables['entryTypeId']);

				if (!$variables['entryType'] || $variables['entryType']->sectionId != $variables['section']->id)
				{
					throw new HttpException(404);
				}
			}

			$variables['title'] = $variables['entryType']->name;
		}
		else
		{
			if (empty($variables['entryType']))
			{
				$variables['entryType'] = new EntryTypeModel();
				$variables['entryType']->sectionId = $variables['section']->id;
			}

			$variables['title'] = Craft::t('Create a new {section} entry type', array('section' => $variables['section']->name));
		}

		$variables['crumbs'] = array(
			array('label' => Craft::t('Settings'), 'url' => UrlHelper::getUrl('settings')),
			array('label' => Craft::t('Sections'), 'url' => UrlHelper::getUrl('settings/sections')),
			array('label' => $variables['section']->name, 'url' => UrlHelper::getUrl('settings/sections/'.$variables['section']->id)),
			array('label' => Craft::t('Entry Types'), 'url' => UrlHelper::getUrl('settings/sections/'.$variables['sectionId'].'/entrytypes')),
		);

		$variables['tabs'] = array(
			'settings'    => array('label' => Craft::t('Settings'),     'url' => '#entrytype-settings'),
			'fieldlayout' => array('label' => Craft::t('Field Layout'), 'url' => '#entrytype-fieldlayout'),
		);

		$this->renderTemplate('settings/sections/_entrytypes/edit', $variables);
	}

	/**
	 * Saves an entry type
	 */
	public function actionSaveEntryType()
	{
		$this->requirePostRequest();

		$entryType = new EntryTypeModel();

		// Set the simple stuff
		$entryType->id         = craft()->request->getPost('entryTypeId');
		$entryType->sectionId  = craft()->request->getRequiredPost('sectionId');
		$entryType->name       = craft()->request->getPost('name');
		$entryType->handle     = craft()->request->getPost('handle');
		$entryType->titleLabel = craft()->request->getPost('titleLabel');

		// Set the field layout
		$fieldLayout = craft()->fields->assembleLayoutFromPost();
		$fieldLayout->type = ElementType::Entry;
		$entryType->setFieldLayout($fieldLayout);

		// Save it
		if (craft()->sections->saveEntryType($entryType))
		{
			craft()->userSession->setNotice(Craft::t('Entry type saved.'));
			$this->redirectToPostedUrl($entryType);
		}
		else
		{
			craft()->userSession->setError(Craft::t('Couldn’t save entry type.'));
		}

		// Send the entry type back to the template
		craft()->urlManager->setRouteVariables(array(
			'entryType' => $entryType
		));
	}

	/**
	 * Deletes an entry type.
	 */
	public function actionDeleteEntryType()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$entryTypeId = craft()->request->getRequiredPost('id');

		craft()->sections->deleteEntryTypeById($entryTypeId);
		$this->returnJson(array('success' => true));
	}
}
