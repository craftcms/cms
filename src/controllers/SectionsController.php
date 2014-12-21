<?php
namespace Craft;

/**
 * The SectionsController class is a controller that handles various section and entry type related tasks such as
 * displaying, saving, deleting and reordering them in the control panel.
 *
 * Note that all actions in this controller require administrator access in order to execute.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.controllers
 * @since     1.0
 */
class SectionsController extends BaseController
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseController::init()
	 *
	 * @throws HttpException
	 * @return null
	 */
	public function init()
	{
		// All section actions require an admin
		craft()->userSession->requireAdmin();
	}

	/**
	 * Sections index.
	 *
	 * @param array $variables
	 *
	 * @return null
	 */
	public function actionIndex(array $variables = array())
	{
		$variables['sections'] = craft()->sections->getAllSections();

		// Can new sections be added?
		if (craft()->getEdition() == Craft::Personal)
		{
			$variables['maxSections'] = 0;

			foreach (craft()->sections->typeLimits as $limit)
			{
				$variables['maxSections'] += $limit;
			}
		}

		$this->renderTemplate('settings/sections/_index', $variables);
	}

	/**
	 * Edit a section.
	 *
	 * @param array $variables
	 *
	 * @throws HttpException|Exception
	 * @return null
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

		$types = array(SectionType::Single, SectionType::Channel, SectionType::Structure);
		$variables['typeOptions'] = array();

		// Get these strings to be caught by our translation util:
		// Craft::t("Channel") Craft::t("Structure") Craft::t("Single")

		foreach ($types as $type)
		{
			$allowed = (($variables['section']->id && $variables['section']->type == $type) || craft()->sections->canHaveMore($type));
			$variables['canBe'.ucfirst($type)] = $allowed;

			if ($allowed)
			{
				$variables['typeOptions'][$type] = Craft::t(ucfirst($type));
			}
		}

		if (!$variables['typeOptions'])
		{
			throw new Exception(Craft::t('Craft Client or Pro Edition is required to create any additional sections.'));
		}

		if (!$variables['section']->type)
		{
			if ($variables['canBeChannel'])
			{
				$variables['section']->type = SectionType::Channel;
			}
			else
			{
				$variables['section']->type = SectionType::Single;
			}
		}

		$variables['canBeHomepage']  = (
			($variables['section']->id && $variables['section']->isHomepage()) ||
			($variables['canBeSingle'] && !craft()->sections->doesHomepageExist())
		);

		$variables['crumbs'] = array(
			array('label' => Craft::t('Settings'), 'url' => UrlHelper::getUrl('settings')),
			array('label' => Craft::t('Sections'), 'url' => UrlHelper::getUrl('settings/sections')),
		);

		$this->renderTemplate('settings/sections/_edit', $variables);
	}

	/**
	 * Saves a section.
	 *
	 * @return null
	 */
	public function actionSaveSection()
	{
		$this->requirePostRequest();

		$section = new SectionModel();

		// Shared attributes
		$section->id               = craft()->request->getPost('sectionId');
		$section->name             = craft()->request->getPost('name');
		$section->handle           = craft()->request->getPost('handle');
		$section->type             = craft()->request->getPost('type');
		$section->enableVersioning = craft()->request->getPost('enableVersioning', true);

		// Type-specific attributes
		$section->hasUrls    = (bool) craft()->request->getPost('types.'.$section->type.'.hasUrls', true);
		$section->template   = craft()->request->getPost('types.'.$section->type.'.template');
		$section->maxLevels  = craft()->request->getPost('types.'.$section->type.'.maxLevels');

		// Locale-specific attributes
		$locales = array();

		if (craft()->isLocalized())
		{
			$localeIds = craft()->request->getPost('locales', array());
		}
		else
		{
			$primaryLocaleId = craft()->i18n->getPrimarySiteLocaleId();
			$localeIds = array($primaryLocaleId);
		}

		$isHomepage = ($section->type == SectionType::Single && craft()->request->getPost('types.'.$section->type.'.homepage'));

		foreach ($localeIds as $localeId)
		{
			if ($isHomepage)
			{
				$urlFormat       = '__home__';
				$nestedUrlFormat = null;
			}
			else
			{
				$urlFormat       = craft()->request->getPost('types.'.$section->type.'.urlFormat.'.$localeId);
				$nestedUrlFormat = craft()->request->getPost('types.'.$section->type.'.nestedUrlFormat.'.$localeId);
			}

			$locales[$localeId] = new SectionLocaleModel(array(
				'locale'           => $localeId,
				'enabledByDefault' => (bool) craft()->request->getPost('defaultLocaleStatuses.'.$localeId),
				'urlFormat'        => $urlFormat,
				'nestedUrlFormat'  => $nestedUrlFormat,
			));
		}

		$section->setLocales($locales);

		$section->hasUrls    = (bool) craft()->request->getPost('types.'.$section->type.'.hasUrls', true);

		// Save it
		if (craft()->sections->saveSection($section))
		{
			craft()->userSession->setNotice(Craft::t('Section saved.'));

			if (isset($_POST['redirect']) && mb_strpos($_POST['redirect'], '{sectionId}') !== false)
			{
				craft()->deprecator->log('SectionsController::saveSection():sectionId_redirect', 'The {sectionId} token within the ‘redirect’ param on sections/saveSection requests has been deprecated. Use {id} instead.');
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
	 *
	 * @return null
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
	 *
	 * @throws HttpException
	 * @return null
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
	 *
	 * @throws HttpException
	 * @return null
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

		$this->renderTemplate('settings/sections/_entrytypes/edit', $variables);
	}

	/**
	 * Saves an entry type.
	 *
	 * @throws Exception
	 * @throws HttpException
	 * @throws \Exception
	 * @return null
	 */
	public function actionSaveEntryType()
	{
		$this->requirePostRequest();

		$entryTypeId = craft()->request->getPost('entryTypeId');

		if ($entryTypeId)
		{
			$entryType = craft()->sections->getEntryTypeById($entryTypeId);

			if (!$entryType)
			{
				throw new Exception(Craft::t('No entry type exists with the ID “{id}”.', array('id' => $entryTypeId)));
			}
		}
		else
		{
			$entryType = new EntryTypeModel();
		}

		// Set the simple stuff
		$entryType->sectionId     = craft()->request->getRequiredPost('sectionId', $entryType->sectionId);
		$entryType->name          = craft()->request->getPost('name', $entryType->name);
		$entryType->handle        = craft()->request->getPost('handle', $entryType->handle);
		$entryType->hasTitleField = (bool) craft()->request->getPost('hasTitleField', $entryType->hasTitleField);
		$entryType->titleLabel    = craft()->request->getPost('titleLabel', $entryType->titleLabel);
		$entryType->titleFormat   = craft()->request->getPost('titleFormat', $entryType->titleFormat);

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
	 * Reorders entry types.
	 *
	 * @return null
	 */
	public function actionReorderEntryTypes()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$entryTypeIds = JsonHelper::decode(craft()->request->getRequiredPost('ids'));
		craft()->sections->reorderEntryTypes($entryTypeIds);

		$this->returnJson(array('success' => true));
	}

	/**
	 * Deletes an entry type.
	 *
	 * @return null
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
