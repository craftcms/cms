<?php
namespace Craft;

/**
 * The EntriesController class is a controller that handles various entry related tasks such as retrieving, saving,
 * swapping between entry types, previewing, deleting and sharing entries.
 *
 * Note that all actions in the controller except {@link actionViewSharedEntry} require an authenticated Craft session
 * via {@link BaseController::allowAnonymous}.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.controllers
 * @since     1.0
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
	 * login on a few, it's best to use {@link UserSessionService::requireLogin() craft()->userSession->requireLogin()}
	 * in the individual methods.
	 *
	 * @var bool
	 */
	protected $allowAnonymous = array('actionViewSharedEntry');

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
	public function actionEditEntry(array $variables = array())
	{
		$this->_prepEditEntryVariables($variables);

		// Make sure they have permission to edit this entry
		$this->enforceEditEntryPermissions($variables['entry']);

		$currentUser = craft()->userSession->getUser();

		$variables['permissionSuffix'] = ':'.$variables['entry']->sectionId;

		if (craft()->getEdition() >= Craft::Client && $variables['section']->type != SectionType::Single)
		{
			// Get all the possible authors
			if ($variables['entry']->authorId)
			{
				if ($variables['entry']->authorId == $currentUser->id)
				{
					$excludeAuthorIds = 'not '.$currentUser->id;
					$excludeAuthorIds = array('and', $excludeAuthorIds, 'not '.$variables['entry']->authorId);
				}
				else
				{
					$excludeAuthorIds = array('not '.$variables['entry']->authorId);
				}
			}

			$authorOptionCriteria = craft()->elements->getCriteria(ElementType::User);
			$authorOptionCriteria->can = 'editEntries'.$variables['permissionSuffix'];
			$authorOptionCriteria->limit = null;

			if ($variables['entry']->authorId)
			{
				$authorOptionCriteria->id = $excludeAuthorIds;
			}

			$authorOptions = $authorOptionCriteria->find();

			// List the current author first
			if ($variables['entry']->authorId && $variables['entry']->authorId != $currentUser->id)
			{
				$currentAuthor = craft()->users->getUserById($variables['entry']->authorId);

				if ($currentAuthor)
				{
					array_unshift($authorOptions, $currentAuthor);
				}
			}

			// Then the current user
			if (!$variables['entry']->authorId || $variables['entry']->authorId == $currentUser->id)
			{
				array_unshift($authorOptions, $currentUser);
			}

			$variables['authorOptions'] = array();

			foreach ($authorOptions as $authorOption)
			{
				$authorLabel = $authorOption->username;
				$authorFullName = $authorOption->getFullName();

				if ($authorFullName)
				{
					$authorLabel .= ' - '.$authorFullName;
				}

				$variables['authorOptions'][] = array('label' => $authorLabel, 'value' => $authorOption->id);
			}
		}

		if (craft()->getEdition() >= Craft::Client && $variables['section']->type == SectionType::Structure)
		{
			// Get all the possible parent options
			$parentOptionCriteria = craft()->elements->getCriteria(ElementType::Entry);
			$parentOptionCriteria->locale = $variables['localeId'];
			$parentOptionCriteria->sectionId = $variables['section']->id;
			$parentOptionCriteria->status = null;
			$parentOptionCriteria->localeEnabled = null;
			$parentOptionCriteria->limit = null;

			if ($variables['section']->maxLevels)
			{
				$parentOptionCriteria->level = '< '.$variables['section']->maxLevels;
			}

			if ($variables['entry']->id)
			{
				// Prevent the current entry, or any of its descendants, from being options
				$idParam = array('and', 'not '.$variables['entry']->id);

				$descendantCriteria = craft()->elements->getCriteria(ElementType::Entry);
				$descendantCriteria->descendantOf = $variables['entry'];
				$descendantCriteria->status = null;
				$descendantCriteria->localeEnabled = null;
				$descendantIds = $descendantCriteria->ids();

				foreach ($descendantIds as $id)
				{
					$idParam[] = 'not '.$id;
				}

				$parentOptionCriteria->id = $idParam;
			}

			$parentOptions = $parentOptionCriteria->find();

			$variables['parentOptions'] = array(array(
				'label' => '', 'value' => '0'
			));

			foreach ($parentOptions as $parentOption)
			{
				$label = '';

				for ($i = 1; $i < $parentOption->level; $i++)
				{
					$label .= '    ';
				}

				$label .= $parentOption->title;

				$variables['parentOptions'][] = array('label' => $label, 'value' => $parentOption->id);
			}

			// Get the initially selected parent
			$variables['parentId'] = craft()->request->getParam('parentId');

			if ($variables['parentId'] === null && $variables['entry']->id)
			{
				$parentIds = $variables['entry']->getAncestors(1)->status(null)->localeEnabled(null)->ids();

				if ($parentIds)
				{
					$variables['parentId'] = $parentIds[0];
				}
			}
		}

		// Get the enabled locales
		if (craft()->isLocalized())
		{
			if ($variables['entry']->id)
			{
				$variables['enabledLocales'] = craft()->elements->getEnabledLocalesForElement($variables['entry']->id);
			}
			else
			{
				$variables['enabledLocales'] = array();

				foreach ($variables['section']->getLocales() as $locale)
				{
					if ($locale->enabledByDefault)
					{
						$variables['enabledLocales'][] = $locale->locale;
					}
				}
			}
		}

		// Page title w/ revision label
		if (craft()->getEdition() >= Craft::Client)
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
					$variables['revisionLabel'] = Craft::t('Version {num}', array('num' => $variables['entry']->num));
					break;
				}

				default:
				{
					$variables['revisionLabel'] = Craft::t('Current');
				}
			}
		}

		if (!$variables['entry']->id)
		{
			$variables['title'] = Craft::t('Create a new entry');
		}
		else
		{
			$variables['docTitle'] = Craft::t($variables['entry']->title);
			$variables['title'] = Craft::t($variables['entry']->title);

			if (craft()->getEdition() >= Craft::Client && $variables['entry']->getClassHandle() != 'Entry')
			{
				$variables['docTitle'] .= ' ('.$variables['revisionLabel'].')';
			}
		}

		// Breadcrumbs
		$variables['crumbs'] = array(
			array('label' => Craft::t('Entries'), 'url' => UrlHelper::getUrl('entries'))
		);

		if ($variables['section']->type == SectionType::Single)
		{
			$variables['crumbs'][] = array('label' => Craft::t('Singles'), 'url' => UrlHelper::getUrl('entries/singles'));
		}
		else
		{
			$variables['crumbs'][] = array('label' => Craft::t($variables['section']->name), 'url' => UrlHelper::getUrl('entries/'.$variables['section']->handle));

			if ($variables['section']->type == SectionType::Structure)
			{
				foreach ($variables['entry']->getAncestors() as $ancestor)
				{
					$variables['crumbs'][] = array('label' => $ancestor->title, 'url' => $ancestor->getCpEditUrl());
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
				$variables['entryTypeOptions'][] = array('label' => Craft::t($entryType->name), 'value' => $entryType->id);
			}

			craft()->templates->includeJsResource('js/EntryTypeSwitcher.js');
			craft()->templates->includeJs('new Craft.EntryTypeSwitcher();');
		}
		else
		{
			$variables['showEntryTypes'] = false;
		}

		// Enable preview mode?
		if (!craft()->request->isMobileBrowser(true) && craft()->sections->isSectionTemplateValid($variables['section']))
		{
			craft()->templates->includeJsResource('js/LivePreview.js');
			craft()->templates->includeJs('Craft.livePreview = new Craft.LivePreview('.JsonHelper::encode($variables['entry']->getUrl()).', "'.$variables['entry']->locale.'");');
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
							$shareParams = array('draftId' => $variables['entry']->draftId);
							break;
						}
						case 'EntryVersion':
						{
							$shareParams = array('versionId' => $variables['entry']->versionId);
							break;
						}
						default:
						{
							$shareParams = array('entryId' => $variables['entry']->id, 'locale' => $variables['entry']->locale);
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
			(craft()->isLocalized() && craft()->getLanguage() != $variables['localeId'] ? '/'.$variables['localeId'] : '');

		// Can the user delete the entry?
		$variables['canDeleteEntry'] = $variables['entry']->id && (
			($variables['entry']->authorId == $currentUser->id && $currentUser->can('deleteEntries'.$variables['permissionSuffix'])) ||
			($variables['entry']->authorId != $currentUser->id && $currentUser->can('deletePeerEntries'.$variables['permissionSuffix']))
		);

		// Include translations
		craft()->templates->includeTranslations('Live Preview');

		// Render the template!
		craft()->templates->includeCssResource('css/entry.css');
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

		$paneHtml = craft()->templates->render('_includes/tabs', $variables) .
			craft()->templates->render('entries/_fields', $variables);

		$this->returnJson(array(
			'paneHtml' => $paneHtml,
			'headHtml' => craft()->templates->getHeadHtml(),
			'footHtml' => craft()->templates->getFootHtml(),
		));
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
		$versionId = craft()->request->getPost('versionId');

		if ($versionId)
		{
			$entry = craft()->entryRevisions->getVersionById($versionId);

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
		$userSessionService = craft()->userSession;
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
					$userSessionService->requirePermission('publishPeerEntries:'.$entry->sectionId);
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
				$userSessionService->requirePermission('publishEntries:'.$entry->sectionId);
			}
			else if (!$currentUser->can('publishEntries:'.$entry->sectionId))
			{
				$entry->enabled = false;
			}
		}

		// Save the entry (finally!)
		if (craft()->entries->saveEntry($entry))
		{
			if (craft()->request->isAjaxRequest())
			{
				$return['success']   = true;
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
				$userSessionService->setNotice(Craft::t('Entry saved.'));

				if (isset($_POST['redirect']) && mb_strpos($_POST['redirect'], '{entryId}') !== false)
				{
					craft()->deprecator->log('EntriesController::actionSaveEntry():entryId_redirect', 'The {entryId} token within the ‘redirect’ param on entries/saveEntry requests has been deprecated. Use {id} instead.');
					$_POST['redirect'] = str_replace('{entryId}', '{id}', $_POST['redirect']);
				}

				$this->redirectToPostedUrl($entry);
			}
		}
		else
		{
			if (craft()->request->isAjaxRequest())
			{
				$this->returnJson(array(
					'errors' => $entry->getErrors(),
				));
			}
			else
			{
				$userSessionService->setError(Craft::t('Couldn’t save entry.'));

				// Send the entry back to the template
				craft()->urlManager->setRouteVariables(array(
					'entry' => $entry
				));
			}
		}
	}

	/**
	 * Deletes an entry.
	 *
	 * @return null
	 */
	public function actionDeleteEntry()
	{
		$this->requirePostRequest();

		$entryId = craft()->request->getRequiredPost('entryId');
		$localeId = craft()->request->getPost('locale');
		$entry = craft()->entries->getEntryById($entryId, $localeId);
		$currentUser = craft()->userSession->getUser();

		if ($entry->authorId == $currentUser->id)
		{
			craft()->userSession->requirePermission('deleteEntries:'.$entry->sectionId);
		}
		else
		{
			craft()->userSession->requirePermission('deletePeerEntries:'.$entry->sectionId);
		}

		if (craft()->entries->deleteEntry($entry))
		{
			if (craft()->request->isAjaxRequest())
			{
				$this->returnJson(array('success' => true));
			}
			else
			{
				craft()->userSession->setNotice(Craft::t('Entry deleted.'));
				$this->redirectToPostedUrl($entry);
			}
		}
		else
		{
			if (craft()->request->isAjaxRequest())
			{
				$this->returnJson(array('success' => false));
			}
			else
			{
				craft()->userSession->setError(Craft::t('Couldn’t delete entry.'));

				// Send the entry back to the template
				craft()->urlManager->setRouteVariables(array(
					'entry' => $entry
				));
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
			$entry = craft()->entries->getEntryById($entryId, $locale);

			if (!$entry)
			{
				throw new HttpException(404);
			}

			$params = array('entryId' => $entryId, 'locale' => $entry->locale);
		}
		else if ($draftId)
		{
			$entry = craft()->entryRevisions->getDraftById($draftId);

			if (!$entry)
			{
				throw new HttpException(404);
			}

			$params = array('draftId' => $draftId);
		}
		else if ($versionId)
		{
			$entry = craft()->entryRevisions->getVersionById($versionId);

			if (!$entry)
			{
				throw new HttpException(404);
			}

			$params = array('versionId' => $versionId);
		}
		else
		{
			throw new HttpException(404);
		}

		// Make sure they have permission to be viewing this entry
		$this->enforceEditEntryPermissions($entry);

		// Make sure the entry actually can be viewed
		if (!craft()->sections->isSectionTemplateValid($entry->getSection()))
		{
			throw new HttpException(404);
		}

		// Create the token and redirect to the entry URL with the token in place
		$token = craft()->tokens->createToken(array('action' => 'entries/viewSharedEntry', 'params' => $params));
		$url = UrlHelper::getUrlWithToken($entry->getUrl(), $token);
		craft()->request->redirect($url);
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
			$entry = craft()->entries->getEntryById($entryId, $locale);
		}
		else if ($draftId)
		{
			$entry = craft()->entryRevisions->getDraftById($draftId);
		}
		else if ($versionId)
		{
			$entry = craft()->entryRevisions->getVersionById($versionId);
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
		if (!empty($variables['sectionHandle']))
		{
			$variables['section'] = craft()->sections->getSectionByHandle($variables['sectionHandle']);
		}
		else if (!empty($variables['sectionId']))
		{
			$variables['section'] = craft()->sections->getSectionById($variables['sectionId']);
		}

		if (empty($variables['section']))
		{
			throw new HttpException(404);
		}

		if (craft()->isLocalized())
		{
			// Only use the locales that the user has access to
			$sectionLocaleIds = array_keys($variables['section']->getLocales());
			$editableLocaleIds = craft()->i18n->getEditableLocaleIds();
			$variables['localeIds'] = array_merge(array_intersect($sectionLocaleIds, $editableLocaleIds));
		}
		else
		{
			$variables['localeIds'] = array(craft()->i18n->getPrimarySiteLocaleId());
		}

		if (!$variables['localeIds'])
		{
			throw new HttpException(403, Craft::t('Your account doesn’t have permission to edit any of this section’s locales.'));
		}

		if (empty($variables['localeId']))
		{
			$variables['localeId'] = craft()->language;

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

		// Now let's set up the actual entry
		if (empty($variables['entry']))
		{
			if (!empty($variables['entryId']))
			{
				if (!empty($variables['draftId']))
				{
					$variables['entry'] = craft()->entryRevisions->getDraftById($variables['draftId']);
				}
				else if (!empty($variables['versionId']))
				{
					$variables['entry'] = craft()->entryRevisions->getVersionById($variables['versionId']);
				}
				else
				{
					$variables['entry'] = craft()->entries->getEntryById($variables['entryId'], $variables['localeId']);
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
				$variables['entry']->authorId = craft()->userSession->getUser()->id;
				$variables['entry']->enabled = true;

				if (!empty($variables['localeId']))
				{
					$variables['entry']->locale = $variables['localeId'];
				}
			}
		}

		// Entry type

		// Override the entry type?
		$typeId = craft()->request->getParam('typeId');

		if ($typeId)
		{
			$variables['entry']->typeId = $typeId;
		}

		// Save the entry type locally
		$variables['entryType'] = $variables['entry']->getType();

		if (!$variables['entryType'])
		{
			throw new Exception(Craft::t('No entry types are available for this entry.'));
		}

		// Tabs
		$variables['tabs'] = array();

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

			$variables['tabs'][] = array(
				'label' => Craft::t($tab->name),
				'url'   => '#tab'.($index+1),
				'class' => ($hasErrors ? 'error' : null)
			);
		}

		// Settings tab
		if ($variables['section']->type != SectionType::Single)
		{
			$hasErrors = ($variables['entry']->hasErrors() && (
				$variables['entry']->getErrors('slug') ||
				$variables['entry']->getErrors('postDate') ||
				$variables['entry']->getErrors('expiryDate')
			));
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
		$entryId = craft()->request->getPost('entryId');
		$localeId = craft()->request->getPost('locale');

		if ($entryId)
		{
			$entry = craft()->entries->getEntryById($entryId, $localeId);

			if (!$entry)
			{
				throw new Exception(Craft::t('No entry exists with the ID “{id}”', array('id' => $entryId)));
			}
		}
		else
		{
			$entry = new EntryModel();
			$entry->sectionId = craft()->request->getRequiredPost('sectionId');

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
		$entry->typeId        = craft()->request->getPost('typeId',    $entry->typeId);
		$entry->authorId      = craft()->request->getPost('author',    ($entry->authorId ? $entry->authorId : craft()->userSession->getUser()->id));
		$entry->slug          = craft()->request->getPost('slug',      $entry->slug);
		$entry->postDate      = (($postDate   = craft()->request->getPost('postDate'))   ? DateTime::createFromString($postDate,   craft()->timezone) : $entry->postDate);
		$entry->expiryDate    = (($expiryDate = craft()->request->getPost('expiryDate')) ? DateTime::createFromString($expiryDate, craft()->timezone) : null);
		$entry->enabled       = (bool) craft()->request->getPost('enabled', $entry->enabled);
		$entry->localeEnabled = (bool) craft()->request->getPost('localeEnabled', $entry->localeEnabled);

		$entry->getContent()->title = craft()->request->getPost('title', $entry->title);

		$fieldsLocation = craft()->request->getParam('fieldsLocation', 'fields');
		$entry->setContentFromPost($fieldsLocation);

		$entry->parentId      = craft()->request->getPost('parentId');
		$entry->revisionNotes = craft()->request->getPost('revisionNotes');
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

		if ($section && $type)
		{
			craft()->setLanguage($entry->locale);

			if (!$entry->postDate)
			{
				$entry->postDate = new DateTime();
			}

			craft()->templates->getTwig()->disableStrictVariables();

			$this->renderTemplate($section->template, array(
				'entry' => $entry
			));
		}
		else
		{
			Craft::log('Attempting to preview an entry that doesn’t have a section/type', LogLevel::Error);
			throw new HttpException(404);
		}
	}
}
