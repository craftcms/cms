<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\controllers;

use Craft;
use craft\app\enums\ElementType;
use craft\app\enums\SectionType;
use craft\app\errors\Exception;
use craft\app\errors\HttpException;
use craft\app\helpers\JsonHelper;
use craft\app\helpers\UrlHelper;
use craft\app\models\EntryType as EntryTypeModel;
use craft\app\models\Section as SectionModel;
use craft\app\models\SectionLocale as SectionLocaleModel;
use craft\app\web\Controller;

/**
 * The SectionsController class is a controller that handles various section and entry type related tasks such as
 * displaying, saving, deleting and reordering them in the control panel.
 *
 * Note that all actions in this controller require administrator access in order to execute.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class SectionsController extends Controller
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc Controller::init()
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
	public function actionIndex(array $variables = [])
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
	public function actionEditSection(array $variables = [])
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

			$variables['title'] = Craft::t('app', 'Create a new section');
		}

		$types = [SectionType::Single, SectionType::Channel, SectionType::Structure];
		$variables['typeOptions'] = [];

		// Get these strings to be caught by our translation util:
		// Craft::t('app', 'Channel') Craft::t('app', 'Structure') Craft::t('app', 'Single')

		foreach ($types as $type)
		{
			$allowed = (($variables['section']->id && $variables['section']->type == $type) || Craft::$app->sections->canHaveMore($type));
			$variables['canBe'.ucfirst($type)] = $allowed;

			if ($allowed)
			{
				$variables['typeOptions'][$type] = Craft::t('app', ucfirst($type));
			}
		}

		if (!$variables['typeOptions'])
		{
			throw new Exception(Craft::t('app', 'Craft Client or Pro Edition is required to create any additional sections.'));
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
			['label' => Craft::t('app', 'Settings'), 'url' => UrlHelper::getUrl('settings')],
			['label' => Craft::t('app', 'Sections'), 'url' => UrlHelper::getUrl('settings/sections')],
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
		$section->id               = Craft::$app->getRequest()->getBodyParam('sectionId');
		$section->name             = Craft::$app->getRequest()->getBodyParam('name');
		$section->handle           = Craft::$app->getRequest()->getBodyParam('handle');
		$section->type             = Craft::$app->getRequest()->getBodyParam('type');
		$section->enableVersioning = Craft::$app->getRequest()->getBodyParam('enableVersioning', true);

		// Type-specific attributes
		$section->hasUrls    = (bool) Craft::$app->getRequest()->getBodyParam('types.'.$section->type.'.hasUrls', true);
		$section->template   = Craft::$app->getRequest()->getBodyParam('types.'.$section->type.'.template');
		$section->maxLevels  = Craft::$app->getRequest()->getBodyParam('types.'.$section->type.'.maxLevels');

		// Locale-specific attributes
		$locales = [];

		if (Craft::$app->isLocalized())
		{
			$localeIds = Craft::$app->getRequest()->getBodyParam('locales', []);
		}
		else
		{
			$primaryLocaleId = Craft::$app->getI18n()->getPrimarySiteLocaleId();
			$localeIds = [$primaryLocaleId];
		}

		$isHomepage = ($section->type == SectionType::Single && Craft::$app->getRequest()->getBodyParam('types.'.$section->type.'.homepage'));

		foreach ($localeIds as $localeId)
		{
			if ($isHomepage)
			{
				$urlFormat       = '__home__';
				$nestedUrlFormat = null;
			}
			else
			{
				$urlFormat       = Craft::$app->getRequest()->getBodyParam('types.'.$section->type.'.urlFormat.'.$localeId);
				$nestedUrlFormat = Craft::$app->getRequest()->getBodyParam('types.'.$section->type.'.nestedUrlFormat.'.$localeId);
			}

			$locales[$localeId] = new SectionLocaleModel([
				'locale'           => $localeId,
				'enabledByDefault' => (bool) Craft::$app->getRequest()->getBodyParam('defaultLocaleStatuses.'.$localeId),
				'urlFormat'        => $urlFormat,
				'nestedUrlFormat'  => $nestedUrlFormat,
			]);
		}

		$section->setLocales($locales);

		$section->hasUrls    = (bool) Craft::$app->getRequest()->getBodyParam('types.'.$section->type.'.hasUrls', true);

		// Save it
		if (Craft::$app->sections->saveSection($section))
		{
			Craft::$app->getSession()->setNotice(Craft::t('app', 'Section saved.'));
			$this->redirectToPostedUrl($section);
		}
		else
		{
			Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t save section.'));
		}

		// Send the section back to the template
		Craft::$app->getUrlManager()->setRouteVariables([
			'section' => $section
		]);
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

		$sectionId = Craft::$app->getRequest()->getRequiredBodyParam('id');

		Craft::$app->sections->deleteSectionById($sectionId);
		$this->returnJson(['success' => true]);
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
	public function actionEntryTypesIndex(array $variables = [])
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

		$variables['crumbs'] = [
			['label' => Craft::t('app', 'Settings'), 'url' => UrlHelper::getUrl('settings')],
			['label' => Craft::t('app', 'Sections'), 'url' => UrlHelper::getUrl('settings/sections')],
			['label' => $variables['section']->name, 'url' => UrlHelper::getUrl('settings/sections/'.$variables['section']->id)],
		];

		$variables['title'] = Craft::t('app', '{section} Entry Types', ['section' => $variables['section']->name]);

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
	public function actionEditEntryType(array $variables = [])
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

			$variables['title'] = Craft::t('app', 'Create a new {section} entry type', ['section' => $variables['section']->name]);
		}

		$variables['crumbs'] = [
			['label' => Craft::t('app', 'Settings'), 'url' => UrlHelper::getUrl('settings')],
			['label' => Craft::t('app', 'Sections'), 'url' => UrlHelper::getUrl('settings/sections')],
			['label' => $variables['section']->name, 'url' => UrlHelper::getUrl('settings/sections/'.$variables['section']->id)],
			['label' => Craft::t('app', 'Entry Types'), 'url' => UrlHelper::getUrl('settings/sections/'.$variables['sectionId'].'/entrytypes')],
		];

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

		$entryTypeId = Craft::$app->getRequest()->getBodyParam('entryTypeId');

		if ($entryTypeId)
		{
			$entryType = Craft::$app->sections->getEntryTypeById($entryTypeId);

			if (!$entryType)
			{
				throw new Exception(Craft::t('app', 'No entry type exists with the ID “{id}”.', ['id' => $entryTypeId]));
			}
		}
		else
		{
			$entryType = new EntryTypeModel();
		}

		// Set the simple stuff
		$entryType->sectionId     = Craft::$app->getRequest()->getRequiredBodyParam('sectionId', $entryType->sectionId);
		$entryType->name          = Craft::$app->getRequest()->getBodyParam('name', $entryType->name);
		$entryType->handle        = Craft::$app->getRequest()->getBodyParam('handle', $entryType->handle);
		$entryType->hasTitleField = (bool) Craft::$app->getRequest()->getBodyParam('hasTitleField', $entryType->hasTitleField);
		$entryType->titleLabel    = Craft::$app->getRequest()->getBodyParam('titleLabel', $entryType->titleLabel);
		$entryType->titleFormat   = Craft::$app->getRequest()->getBodyParam('titleFormat', $entryType->titleFormat);

		// Set the field layout
		$fieldLayout = Craft::$app->fields->assembleLayoutFromPost();
		$fieldLayout->type = ElementType::Entry;
		$entryType->setFieldLayout($fieldLayout);

		// Save it
		if (Craft::$app->sections->saveEntryType($entryType))
		{
			Craft::$app->getSession()->setNotice(Craft::t('app', 'Entry type saved.'));
			$this->redirectToPostedUrl($entryType);
		}
		else
		{
			Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t save entry type.'));
		}

		// Send the entry type back to the template
		Craft::$app->getUrlManager()->setRouteVariables([
			'entryType' => $entryType
		]);
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

		$entryTypeIds = JsonHelper::decode(Craft::$app->getRequest()->getRequiredBodyParam('ids'));
		Craft::$app->sections->reorderEntryTypes($entryTypeIds);

		$this->returnJson(['success' => true]);
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

		$entryTypeId = Craft::$app->getRequest()->getRequiredBodyParam('id');

		Craft::$app->sections->deleteEntryTypeById($entryTypeId);
		$this->returnJson(['success' => true]);
	}
}
