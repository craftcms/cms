<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\controllers;

use craft\app\Craft;
use craft\app\enums\ElementType;
use craft\app\enums\SectionType;
use craft\app\errors\Exception;
use craft\app\helpers\JsonHelper;
use craft\app\helpers\UrlHelper;
use craft\app\models\EntryType      as EntryTypeModel;
use craft\app\models\Section        as SectionModel;
use craft\app\models\SectionLocale  as SectionLocaleModel;
use craft\app\errors\HttpException;

/**
 * The SectionsController class is a controller that handles various section and entry type related tasks such as
 * displaying, saving, deleting and reordering them in the control panel.
 *
 * Note that all actions in this controller require administrator access in order to execute.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
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
		$this->requireAdmin();
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
		$variables['sections'] = Craft::$app->sections->getAllSections();

		// Can new sections be added?
		if (Craft::$app->getEdition() == Craft::Personal)
		{
			$variables['maxSections'] = 0;

			foreach (Craft::$app->sections->typeLimits as $limit)
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
				$variables['section'] = Craft::$app->sections->getSectionById($variables['sectionId']);

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

		$types = [SectionType::Single, SectionType::Channel, SectionType::Structure];
		$variables['typeOptions'] = [];

		// Get these strings to be caught by our translation util:
		// Craft::t("Channel") Craft::t("Structure") Craft::t("Single")

		foreach ($types as $type)
		{
			$allowed = (($variables['section']->id && $variables['section']->type == $type) || Craft::$app->sections->canHaveMore($type));
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
			($variables['canBeSingle'] && !Craft::$app->sections->doesHomepageExist())
		);

		$variables['crumbs'] = [
			['label' => Craft::t('Settings'), 'url' => UrlHelper::getUrl('settings')],
			['label' => Craft::t('Sections'), 'url' => UrlHelper::getUrl('settings/sections')],
		];

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
		$section->id               = Craft::$app->request->getPost('sectionId');
		$section->name             = Craft::$app->request->getPost('name');
		$section->handle           = Craft::$app->request->getPost('handle');
		$section->type             = Craft::$app->request->getPost('type');
		$section->enableVersioning = Craft::$app->request->getPost('enableVersioning', true);

		// Type-specific attributes
		$section->hasUrls    = (bool) Craft::$app->request->getPost('types.'.$section->type.'.hasUrls', true);
		$section->template   = Craft::$app->request->getPost('types.'.$section->type.'.template');
		$section->maxLevels  = Craft::$app->request->getPost('types.'.$section->type.'.maxLevels');

		// Locale-specific attributes
		$locales = array();

		if (Craft::$app->isLocalized())
		{
			$localeIds = Craft::$app->request->getPost('locales', array());
		}
		else
		{
			$primaryLocaleId = Craft::$app->i18n->getPrimarySiteLocaleId();
			$localeIds = array($primaryLocaleId);
		}

		$isHomepage = ($section->type == SectionType::Single && Craft::$app->request->getPost('types.'.$section->type.'.homepage'));

		foreach ($localeIds as $localeId)
		{
			if ($isHomepage)
			{
				$urlFormat       = '__home__';
				$nestedUrlFormat = null;
			}
			else
			{
				$urlFormat       = Craft::$app->request->getPost('types.'.$section->type.'.urlFormat.'.$localeId);
				$nestedUrlFormat = Craft::$app->request->getPost('types.'.$section->type.'.nestedUrlFormat.'.$localeId);
			}

			$locales[$localeId] = new SectionLocaleModel(array(
				'locale'           => $localeId,
				'enabledByDefault' => (bool) Craft::$app->request->getPost('defaultLocaleStatuses.'.$localeId),
				'urlFormat'        => $urlFormat,
				'nestedUrlFormat'  => $nestedUrlFormat,
			));
		}

		$section->setLocales($locales);

		$section->hasUrls    = (bool) Craft::$app->request->getPost('types.'.$section->type.'.hasUrls', true);

		// Save it
		if (Craft::$app->sections->saveSection($section))
		{
			Craft::$app->getSession()->setNotice(Craft::t('Section saved.'));
			$this->redirectToPostedUrl($section);
		}
		else
		{
			Craft::$app->getSession()->setError(Craft::t('Couldn’t save section.'));
		}

		// Send the section back to the template
		Craft::$app->urlManager->setRouteVariables(array(
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

		$sectionId = Craft::$app->request->getRequiredPost('id');

		Craft::$app->sections->deleteSectionById($sectionId);
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

		$variables['section'] = Craft::$app->sections->getSectionById($variables['sectionId']);

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

		$variables['section'] = Craft::$app->sections->getSectionById($variables['sectionId']);

		if (!$variables['section'])
		{
			throw new HttpException(404);
		}

		if (!empty($variables['entryTypeId']))
		{
			if (empty($variables['entryType']))
			{
				$variables['entryType'] = Craft::$app->sections->getEntryTypeById($variables['entryTypeId']);

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

		$entryTypeId = Craft::$app->request->getPost('entryTypeId');

		if ($entryTypeId)
		{
			$entryType = Craft::$app->sections->getEntryTypeById($entryTypeId);

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
		$entryType->sectionId     = Craft::$app->request->getRequiredPost('sectionId', $entryType->sectionId);
		$entryType->name          = Craft::$app->request->getPost('name', $entryType->name);
		$entryType->handle        = Craft::$app->request->getPost('handle', $entryType->handle);
		$entryType->hasTitleField = (bool) Craft::$app->request->getPost('hasTitleField', $entryType->hasTitleField);
		$entryType->titleLabel    = Craft::$app->request->getPost('titleLabel', $entryType->titleLabel);
		$entryType->titleFormat   = Craft::$app->request->getPost('titleFormat', $entryType->titleFormat);

		// Set the field layout
		$fieldLayout = Craft::$app->fields->assembleLayoutFromPost();
		$fieldLayout->type = ElementType::Entry;
		$entryType->setFieldLayout($fieldLayout);

		// Save it
		if (Craft::$app->sections->saveEntryType($entryType))
		{
			Craft::$app->getSession()->setNotice(Craft::t('Entry type saved.'));
			$this->redirectToPostedUrl($entryType);
		}
		else
		{
			Craft::$app->getSession()->setError(Craft::t('Couldn’t save entry type.'));
		}

		// Send the entry type back to the template
		Craft::$app->urlManager->setRouteVariables(array(
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

		$entryTypeIds = JsonHelper::decode(Craft::$app->request->getRequiredPost('ids'));
		Craft::$app->sections->reorderEntryTypes($entryTypeIds);

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

		$entryTypeId = Craft::$app->request->getRequiredPost('id');

		Craft::$app->sections->deleteEntryTypeById($entryTypeId);
		$this->returnJson(array('success' => true));
	}
}
