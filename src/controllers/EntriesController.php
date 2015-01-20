<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\controllers;

use Craft;
use craft\app\dates\DateTime;
use craft\app\enums\ElementType;
use craft\app\enums\SectionType;
use craft\app\errors\Exception;
use craft\app\errors\HttpException;
use craft\app\helpers\JsonHelper;
use craft\app\helpers\UrlHelper;
use craft\app\models\Entry as EntryModel;
use craft\app\variables\ElementType as ElementTypeVariable;

/**
 * The EntriesController class is a controller that handles various entry related tasks such as retrieving, saving,
 * swapping between entry types, previewing, deleting and sharing entries.
 *
 * Note that all actions in the controller except [[actionViewSharedEntry]] require an authenticated Craft session
 * via [[BaseController::allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class EntriesController extends BaseEntriesController
{
	// Properties
	// =========================================================================

	/**
	 * If set to false, you are required to be logged in to execute any of the given controller's actions.
	 *
	 * If set to true, anonymous access is allowed for all of the given controller's actions.
	 *
	 * If the value is an array of action names, then you must be logged in for any action method except for the ones in
	 * the array list.
	 *
	 * If you have a controller that where the majority of action methods will be anonymous, but you only want require
	 * login on a few, it's best to call [[requireLogin()]] in the individual methods.
	 *
	 * @var bool
	 */
	protected $allowAnonymous = ['actionViewSharedEntry'];

	// Public Methods
	// =========================================================================

	/**
	 * Called when a user beings up an entry for editing before being displayed.
	 *
	 * @param array $variables
	 *
	 * @throws HttpException
	 * @return null
	 */
	public function actionEditEntry(array $variables = [])
	{
		$this->_prepEditEntryVariables($variables);

		// Make sure they have permission to edit this entry
		$this->enforceEditEntryPermissions($variables['entry']);

		$currentUser = Craft::$app->getUser()->getIdentity();

		$variables['permissionSuffix'] = ':'.$variables['entry']->sectionId;

		if (Craft::$app->getEdition() == Craft::Pro && $variables['section']->type != SectionType::Single)
		{
			// Author selector variables
			// ---------------------------------------------------------------------

			$variables['userElementType'] = new ElementTypeVariable(Craft::$app->elements->getElementType(ElementType::User));

			$authorPermission = 'editEntries'.$variables['permissionSuffix'];

			$variables['authorOptionCriteria'] = [
				'can' => $authorPermission,
			];

			$variables['author'] = $variables['entry']->getAuthor();

			if (!$variables['author'])
			{
				// Default to the current user
				$variables['author'] = $currentUser;
			}
		}

		// Parent Entry selector variables
		// ---------------------------------------------------------------------

		if (
			Craft::$app->getEdition() >= Craft::Client &&
			$variables['section']->type == SectionType::Structure &&
			$variables['section']->maxLevels != 1
		)
		{
			$variables['elementType'] = new ElementTypeVariable(Craft::$app->elements->getElementType(ElementType::Entry));

			$variables['parentOptionCriteria'] = [
				'locale'        => $variables['localeId'],
				'sectionId'     => $variables['section']->id,
				'status'        => null,
				'localeEnabled' => null,
			];

			if ($variables['section']->maxLevels)
			{
				$variables['parentOptionCriteria']['level'] = '< '.$variables['section']->maxLevels;
			}

			if ($variables['entry']->id)
			{
				// Prevent the current entry, or any of its descendants, from being options
				$idParam = ['and', 'not '.$variables['entry']->id];

				$descendantCriteria = Craft::$app->elements->getCriteria(ElementType::Entry);
				$descendantCriteria->descendantOf = $variables['entry'];
				$descendantCriteria->status = null;
				$descendantCriteria->localeEnabled = null;
				$descendantIds = $descendantCriteria->ids();

				foreach ($descendantIds as $id)
				{
					$idParam[] = 'not '.$id;
				}

				$variables['parentOptionCriteria']['id'] = $idParam;
			}

			// Get the initially selected parent
			$parentId = Craft::$app->getRequest()->getParam('parentId');

			if ($parentId === null && $variables['entry']->id)
			{
				$parentIds = $variables['entry']->getAncestors(1)->status(null)->localeEnabled(null)->ids();

				if ($parentIds)
				{
					$parentId = $parentIds[0];
				}
			}

			if ($parentId)
			{
				$variables['parent'] = Craft::$app->entries->getEntryById($parentId, $variables['localeId']);
			}
		}

		// Enabled locales
		// ---------------------------------------------------------------------

		if (Craft::$app->isLocalized())
		{
			if ($variables['entry']->id)
			{
				$variables['enabledLocales'] = Craft::$app->elements->getEnabledLocalesForElement($variables['entry']->id);
			}
			else
			{
				$variables['enabledLocales'] = [];

				foreach ($variables['section']->getLocales() as $locale)
				{
					if ($locale->enabledByDefault)
					{
						$variables['enabledLocales'][] = $locale->locale;
					}
				}
			}
		}

		// Other variables
		// ---------------------------------------------------------------------

		// Page title w/ revision label
		if (Craft::$app->getEdition() >= Craft::Client)
		{
			switch ($variables['entry']->getClassHandle())
			{
				case 'EntryDraft':
				{
					$variables['revisionLabel'] = $variables['entry']->name;
					break;
				}

				case 'EntryVersion':
				{
					$variables['revisionLabel'] = Craft::t('app', 'Version {num}', ['num' => $variables['entry']->num]);
					break;
				}

				default:
				{
					$variables['revisionLabel'] = Craft::t('app', 'Current');
				}
			}
		}

		if (!$variables['entry']->id)
		{
			$variables['title'] = Craft::t('app', 'Create a new entry');
		}
		else
		{
			$variables['docTitle'] = Craft::t('app', $variables['entry']->title);
			$variables['title'] = Craft::t('app', $variables['entry']->title);

			if (Craft::$app->getEdition() >= Craft::Client && $variables['entry']->getClassHandle() != 'Entry')
			{
				$variables['docTitle'] .= ' ('.$variables['revisionLabel'].')';
			}
		}

		// Breadcrumbs
		$variables['crumbs'] = [
			['label' => Craft::t('app', 'Entries'), 'url' => UrlHelper::getUrl('entries')]
		];

		if ($variables['section']->type == SectionType::Single)
		{
			$variables['crumbs'][] = ['label' => Craft::t('app', 'Singles'), 'url' => UrlHelper::getUrl('entries/singles')];
		}
		else
		{
			$variables['crumbs'][] = ['label' => Craft::t('app', $variables['section']->name), 'url' => UrlHelper::getUrl('entries/'.$variables['section']->handle)];

			if ($variables['section']->type == SectionType::Structure)
			{
				foreach ($variables['entry']->getAncestors() as $ancestor)
				{
					$variables['crumbs'][] = ['label' => $ancestor->title, 'url' => $ancestor->getCpEditUrl()];
				}
			}
		}

		// Multiple entry types?
		$entryTypes = $variables['section']->getEntryTypes();

		if (count($entryTypes) > 1)
		{
			$variables['showEntryTypes'] = true;

			foreach ($entryTypes as $entryType)
			{
				$variables['entryTypeOptions'][] = ['label' => Craft::t('app', $entryType->name), 'value' => $entryType->id];
			}

			Craft::$app->templates->includeJsResource('js/EntryTypeSwitcher.js');
			Craft::$app->templates->includeJs('new Craft.EntryTypeSwitcher();');
		}
		else
		{
			$variables['showEntryTypes'] = false;
		}

		// Enable Live Preview?
		if (!Craft::$app->getRequest()->getIsMobileBrowser(true) && Craft::$app->sections->isSectionTemplateValid($variables['section']))
		{
			Craft::$app->templates->includeJs('Craft.LivePreview.init('.JsonHelper::encode([
				'fields'        => '#title-field, #fields > div > div > .field',
				'extraFields'   => '#settings',
				'previewUrl'    => $variables['entry']->getUrl(),
				'previewAction' => 'entries/previewEntry',
				'previewParams' => [
				                       'sectionId' => $variables['section']->id,
				                       'entryId'   => $variables['entry']->id,
				                       'locale'    => $variables['entry']->locale,
				                       'versionId' => ($variables['entry']->getClassHandle() == 'EntryVersion' ? $variables['entry']->versionId : null),
				]
				]).');');

			$variables['showPreviewBtn'] = true;

			// Should we show the Share button too?
			if ($variables['entry']->id)
			{
				$classHandle = $variables['entry']->getClassHandle();

				// If we're looking at the live version of an entry, just use
				// the entry's main URL as its share URL
				if ($classHandle == 'Entry' && $variables['entry']->getStatus() == EntryModel::LIVE)
				{
					$variables['shareUrl'] = $variables['entry']->getUrl();
				}
				else
				{
					switch ($classHandle)
					{
						case 'EntryDraft':
						{
							$shareParams = ['draftId' => $variables['entry']->draftId];
							break;
						}
						case 'EntryVersion':
						{
							$shareParams = ['versionId' => $variables['entry']->versionId];
							break;
						}
						default:
						{
							$shareParams = ['entryId' => $variables['entry']->id, 'locale' => $variables['entry']->locale];
							break;
						}
					}

					$variables['shareUrl'] = UrlHelper::getActionUrl('entries/shareEntry', $shareParams);
				}
			}
		}
		else
		{
			$variables['showPreviewBtn'] = false;
		}

		// Set the base CP edit URL

		// Can't just use the entry's getCpEditUrl() because that might include the locale ID when we don't want it
		$variables['baseCpEditUrl'] = 'entries/'.$variables['section']->handle.'/{id}-{slug}';

		// Set the "Continue Editing" URL
		$variables['continueEditingUrl'] = $variables['baseCpEditUrl'] .
			(isset($variables['draftId']) ? '/drafts/'.$variables['draftId'] : '') .
			(Craft::$app->isLocalized() && Craft::$app->getLanguage() != $variables['localeId'] ? '/'.$variables['localeId'] : '');

		// Can the user delete the entry?
		$variables['canDeleteEntry'] = $variables['entry']->id && (
			($variables['entry']->authorId == $currentUser->id && $currentUser->can('deleteEntries'.$variables['permissionSuffix'])) ||
			($variables['entry']->authorId != $currentUser->id && $currentUser->can('deletePeerEntries'.$variables['permissionSuffix']))
		);

		// Include translations
		Craft::$app->templates->includeTranslations('Live Preview');

		// Render the template!
		Craft::$app->templates->includeCssResource('css/entry.css');
		$this->renderTemplate('entries/_edit', $variables);
	}

	/**
	 * Switches between two entry types.
	 *
	 * @return null
	 */
	public function actionSwitchEntryType()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$entry = $this->_getEntryModel();
		$this->enforceEditEntryPermissions($entry);
		$this->_populateEntryModel($entry);

		$variables['sectionId'] = $entry->sectionId;
		$variables['entry'] = $entry;
		$variables['showEntryTypes'] = true;

		$this->_prepEditEntryVariables($variables);

		$paneHtml = Craft::$app->templates->render('_includes/tabs', $variables) .
			Craft::$app->templates->render('entries/_fields', $variables);

		$this->returnJson([
			'paneHtml' => $paneHtml,
			'headHtml' => Craft::$app->templates->getHeadHtml(),
			'footHtml' => Craft::$app->templates->getFootHtml(),
		]);
	}

	/**
	 * Previews an entry.
	 *
	 * @throws HttpException
	 * @return null
	 */
	public function actionPreviewEntry()
	{
		$this->requirePostRequest();

		// Are we previewing a version?
		$versionId = Craft::$app->getRequest()->getBodyParam('versionId');

		if ($versionId)
		{
			$entry = Craft::$app->entryRevisions->getVersionById($versionId);

			if (!$entry)
			{
				throw new HttpException(404);
			}

			$this->enforceEditEntryPermissions($entry);
		}
		else
		{
			$entry = $this->_getEntryModel();
			$this->enforceEditEntryPermissions($entry);
			$this->_populateEntryModel($entry);
		}

		$this->_showEntry($entry);
	}

	/**
	 * Saves an entry.
	 *
	 * @return null
	 */
	public function actionSaveEntry()
	{
		$this->requirePostRequest();

		$entry = $this->_getEntryModel();

		// Permission enforcement
		$this->enforceEditEntryPermissions($entry);
		$userSessionService = Craft::$app->getUser();
		$currentUser = $userSessionService->getUser();

		if ($entry->id)
		{
			// Is this another user's entry (and it's not a Single)?
			if (
				$entry->authorId != $currentUser->id &&
				$entry->getSection()->type != SectionType::Single
			)
			{
				if ($entry->enabled)
				{
					// Make sure they have permission to make live changes to those
					$this->requirePermission('publishPeerEntries:'.$entry->sectionId);
				}
			}
		}

		// Populate the entry with post data
		$this->_populateEntryModel($entry);

		// Even more permission enforcement
		if ($entry->enabled)
		{
			if ($entry->id)
			{
				$this->requirePermission('publishEntries:'.$entry->sectionId);
			}
			else if (!$currentUser->can('publishEntries:'.$entry->sectionId))
			{
				$entry->enabled = false;
			}
		}

		// Save the entry (finally!)
		if (Craft::$app->entries->saveEntry($entry))
		{
			if (Craft::$app->getRequest()->getIsAjax())
			{
				$return['success']   = true;
				$return['id']        = $entry->id;
				$return['title']     = $entry->title;
				$return['cpEditUrl'] = $entry->getCpEditUrl();

				$author = $entry->getAuthor()->getAttributes();

				if (isset($author['password']))
				{
					unset($author['password']);
				}

				$return['author']    = $author;
				$return['postDate']  = ($entry->postDate ? $entry->postDate->localeDate() : null);

				$this->returnJson($return);
			}
			else
			{
				$userSessionService->setNotice(Craft::t('app', 'Entry saved.'));
				$this->redirectToPostedUrl($entry);
			}
		}
		else
		{
			if (Craft::$app->getRequest()->getIsAjax())
			{
				$this->returnJson([
					'errors' => $entry->getErrors(),
				]);
			}
			else
			{
				$userSessionService->setError(Craft::t('app', 'Couldn’t save entry.'));

				// Send the entry back to the template
				Craft::$app->getUrlManager()->setRouteVariables([
					'entry' => $entry
				]);
			}
		}
	}

	/**
	 * Deletes an entry.
	 *
	 * @throws Exception
	 * @throws HttpException
	 * @throws \Exception
	 * @return null
	 */
	public function actionDeleteEntry()
	{
		$this->requirePostRequest();

		$entryId = Craft::$app->getRequest()->getRequiredBodyParam('entryId');
		$localeId = Craft::$app->getRequest()->getBodyParam('locale');
		$entry = Craft::$app->entries->getEntryById($entryId, $localeId);

		if (!$entry)
		{
			throw new Exception(Craft::t('app', 'No entry exists with the ID “{id}”.', ['id' => $entryId]));
		}

		$currentUser = Craft::$app->getUser()->getIdentity();

		if ($entry->authorId == $currentUser->id)
		{
			$this->requirePermission('deleteEntries:'.$entry->sectionId);
		}
		else
		{
			$this->requirePermission('deletePeerEntries:'.$entry->sectionId);
		}

		if (Craft::$app->entries->deleteEntry($entry))
		{
			if (Craft::$app->getRequest()->getIsAjax())
			{
				$this->returnJson(['success' => true]);
			}
			else
			{
				Craft::$app->getSession()->setNotice(Craft::t('app', 'Entry deleted.'));
				$this->redirectToPostedUrl($entry);
			}
		}
		else
		{
			if (Craft::$app->getRequest()->getIsAjax())
			{
				$this->returnJson(['success' => false]);
			}
			else
			{
				Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t delete entry.'));

				// Send the entry back to the template
				Craft::$app->getUrlManager()->setRouteVariables([
					'entry' => $entry
				]);
			}
		}
	}

	/**
	 * Redirects the client to a URL for viewing an entry/draft/version on the front end.
	 *
	 * @param mixed $entryId
	 * @param mixed $locale
	 * @param mixed $draftId
	 * @param mixed $versionId
	 *
	 * @throws HttpException
	 * @return null
	 */
	public function actionShareEntry($entryId = null, $locale = null, $draftId = null, $versionId = null)
	{
		if ($entryId)
		{
			$entry = Craft::$app->entries->getEntryById($entryId, $locale);

			if (!$entry)
			{
				throw new HttpException(404);
			}

			$params = ['entryId' => $entryId, 'locale' => $entry->locale];
		}
		else if ($draftId)
		{
			$entry = Craft::$app->entryRevisions->getDraftById($draftId);

			if (!$entry)
			{
				throw new HttpException(404);
			}

			$params = ['draftId' => $draftId];
		}
		else if ($versionId)
		{
			$entry = Craft::$app->entryRevisions->getVersionById($versionId);

			if (!$entry)
			{
				throw new HttpException(404);
			}

			$params = ['versionId' => $versionId];
		}
		else
		{
			throw new HttpException(404);
		}

		// Make sure they have permission to be viewing this entry
		$this->enforceEditEntryPermissions($entry);

		// Make sure the entry actually can be viewed
		if (!Craft::$app->sections->isSectionTemplateValid($entry->getSection()))
		{
			throw new HttpException(404);
		}

		// Create the token and redirect to the entry URL with the token in place
		$token = Craft::$app->tokens->createToken(['action' => 'entries/viewSharedEntry', 'params' => $params]);
		$url = UrlHelper::getUrlWithToken($entry->getUrl(), $token);
		Craft::$app->getRequest()->redirect($url);
	}

	/**
	 * Shows an entry/draft/version based on a token.
	 *
	 * @param mixed $entryId
	 * @param mixed $locale
	 * @param mixed $draftId
	 * @param mixed $versionId
	 *
	 * @throws HttpException
	 * @return null
	 */
	public function actionViewSharedEntry($entryId = null, $locale = null, $draftId = null, $versionId = null)
	{
		$this->requireToken();

		if ($entryId)
		{
			$entry = Craft::$app->entries->getEntryById($entryId, $locale);
		}
		else if ($draftId)
		{
			$entry = Craft::$app->entryRevisions->getDraftById($draftId);
		}
		else if ($versionId)
		{
			$entry = Craft::$app->entryRevisions->getVersionById($versionId);
		}

		if (!$entry)
		{
			throw new HttpException(404);
		}

		$this->_showEntry($entry);
	}

	// Private Methods
	// =========================================================================

	/**
	 * Preps entry edit variables.
	 *
	 * @param array &$variables
	 *
	 * @throws HttpException|Exception
	 * @return null
	 */
	private function _prepEditEntryVariables(&$variables)
	{
		// Get the section
		// ---------------------------------------------------------------------

		if (!empty($variables['sectionHandle']))
		{
			$variables['section'] = Craft::$app->sections->getSectionByHandle($variables['sectionHandle']);
		}
		else if (!empty($variables['sectionId']))
		{
			$variables['section'] = Craft::$app->sections->getSectionById($variables['sectionId']);
		}

		if (empty($variables['section']))
		{
			throw new HttpException(404);
		}

		// Get the locale
		// ---------------------------------------------------------------------

		if (Craft::$app->isLocalized())
		{
			// Only use the locales that the user has access to
			$sectionLocaleIds = array_keys($variables['section']->getLocales());
			$editableLocaleIds = Craft::$app->getI18n()->getEditableLocaleIds();
			$variables['localeIds'] = array_merge(array_intersect($sectionLocaleIds, $editableLocaleIds));
		}
		else
		{
			$variables['localeIds'] = [Craft::$app->getI18n()->getPrimarySiteLocaleId()];
		}

		if (!$variables['localeIds'])
		{
			throw new HttpException(403, Craft::t('app', 'Your account doesn’t have permission to edit any of this section’s locales.'));
		}

		if (empty($variables['localeId']))
		{
			$variables['localeId'] = Craft::$app->language;

			if (!in_array($variables['localeId'], $variables['localeIds']))
			{
				$variables['localeId'] = $variables['localeIds'][0];
			}
		}
		else
		{
			// Make sure they were requesting a valid locale
			if (!in_array($variables['localeId'], $variables['localeIds']))
			{
				throw new HttpException(404);
			}
		}

		// Get the entry
		// ---------------------------------------------------------------------

		if (empty($variables['entry']))
		{
			if (!empty($variables['entryId']))
			{
				if (!empty($variables['draftId']))
				{
					$variables['entry'] = Craft::$app->entryRevisions->getDraftById($variables['draftId']);
				}
				else if (!empty($variables['versionId']))
				{
					$variables['entry'] = Craft::$app->entryRevisions->getVersionById($variables['versionId']);
				}
				else
				{
					$variables['entry'] = Craft::$app->entries->getEntryById($variables['entryId'], $variables['localeId']);
				}

				if (!$variables['entry'])
				{
					throw new HttpException(404);
				}
			}
			else
			{
				$variables['entry'] = new EntryModel();
				$variables['entry']->sectionId = $variables['section']->id;
				$variables['entry']->authorId = Craft::$app->getUser()->getIdentity()->id;
				$variables['entry']->enabled = true;

				if (!empty($variables['localeId']))
				{
					$variables['entry']->locale = $variables['localeId'];
				}
			}
		}

		// Get the entry type
		// ---------------------------------------------------------------------

		// Override the entry type?
		$typeId = Craft::$app->getRequest()->getParam('typeId');

		if ($typeId)
		{
			$variables['entry']->typeId = $typeId;
		}

		$variables['entryType'] = $variables['entry']->getType();

		if (!$variables['entryType'])
		{
			throw new Exception(Craft::t('app', 'No entry types are available for this entry.'));
		}

		// Define the content tabs
		// ---------------------------------------------------------------------

		$variables['tabs'] = [];

		foreach ($variables['entryType']->getFieldLayout()->getTabs() as $index => $tab)
		{
			// Do any of the fields on this tab have errors?
			$hasErrors = false;

			if ($variables['entry']->hasErrors())
			{
				foreach ($tab->getFields() as $field)
				{
					if ($variables['entry']->getErrors($field->getField()->handle))
					{
						$hasErrors = true;
						break;
					}
				}
			}

			$variables['tabs'][] = [
				'label' => Craft::t('app', $tab->name),
				'url'   => '#tab'.($index+1),
				'class' => ($hasErrors ? 'error' : null)
			];
		}
	}

	/**
	 * Fetches or creates an EntryModel.
	 *
	 * @throws Exception
	 * @return EntryModel
	 */
	private function _getEntryModel()
	{
		$entryId = Craft::$app->getRequest()->getBodyParam('entryId');
		$localeId = Craft::$app->getRequest()->getBodyParam('locale');

		if ($entryId)
		{
			$entry = Craft::$app->entries->getEntryById($entryId, $localeId);

			if (!$entry)
			{
				throw new Exception(Craft::t('app', 'No entry exists with the ID “{id}”.', ['id' => $entryId]));
			}
		}
		else
		{
			$entry = new EntryModel();
			$entry->sectionId = Craft::$app->getRequest()->getRequiredBodyParam('sectionId');

			if ($localeId)
			{
				$entry->locale = $localeId;
			}
		}

		return $entry;
	}

	/**
	 * Populates an EntryModel with post data.
	 *
	 * @param EntryModel $entry
	 *
	 * @return null
	 */
	private function _populateEntryModel(EntryModel $entry)
	{
		// Set the entry attributes, defaulting to the existing values for whatever is missing from the post data
		$entry->typeId        = Craft::$app->getRequest()->getBodyParam('typeId', $entry->typeId);
		$entry->slug          = Craft::$app->getRequest()->getBodyParam('slug', $entry->slug);
		$entry->postDate      = (($postDate   = Craft::$app->getRequest()->getBodyParam('postDate'))   ? DateTime::createFromString($postDate,   Craft::$app->timezone) : $entry->postDate);
		$entry->expiryDate    = (($expiryDate = Craft::$app->getRequest()->getBodyParam('expiryDate')) ? DateTime::createFromString($expiryDate, Craft::$app->timezone) : null);
		$entry->enabled       = (bool) Craft::$app->getRequest()->getBodyParam('enabled', $entry->enabled);
		$entry->localeEnabled = (bool) Craft::$app->getRequest()->getBodyParam('localeEnabled', $entry->localeEnabled);

		$entry->getContent()->title = Craft::$app->getRequest()->getBodyParam('title', $entry->title);

		$fieldsLocation = Craft::$app->getRequest()->getParam('fieldsLocation', 'fields');
		$entry->setContentFromPost($fieldsLocation);

		// Author
		$authorId = Craft::$app->getRequest()->getBodyParam('author', ($entry->authorId ? $entry->authorId : Craft::$app->getUser()->getIdentity()->id));

		if (is_array($authorId))
		{
			$authorId = isset($authorId[0]) ? $authorId[0] : null;
		}

		$entry->authorId = $authorId;

		// Parent
		$parentId = Craft::$app->getRequest()->getBodyParam('parentId');

		if (is_array($parentId))
		{
			$parentId = isset($parentId[0]) ? $parentId[0] : null;
		}

		$entry->newParentId = $parentId;

		// Revision notes
		$entry->revisionNotes = Craft::$app->getRequest()->getBodyParam('revisionNotes');
	}

	/**
	 * Displays an entry.
	 *
	 * @param EntryModel $entry
	 *
	 * @throws HttpException
	 * @return null
	 */
	private function _showEntry(EntryModel $entry)
	{
		$section = $entry->getSection();
		$type = $entry->getType();

		if (!$section || !$type)
		{
			Craft::error('Attempting to preview an entry that doesn’t have a section/type');
			throw new HttpException(404);
		}

		Craft::$app->setLanguage($entry->locale);

		if (!$entry->postDate)
		{
			$entry->postDate = new DateTime();
		}

		// Have this entry override any freshly queried entries with the same ID/locale
		Craft::$app->elements->setPlaceholderElement($entry);

		Craft::$app->templates->getTwig()->disableStrictVariables();

		$this->renderTemplate($section->template, [
			'entry' => $entry
		]);
	}
}
