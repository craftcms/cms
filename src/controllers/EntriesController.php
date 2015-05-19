<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\controllers;

use Craft;
use craft\app\dates\DateTime;
use craft\app\elements\User;
use craft\app\errors\Exception;
use craft\app\errors\HttpException;
use craft\app\helpers\DateTimeHelper;
use craft\app\helpers\JsonHelper;
use craft\app\helpers\UrlHelper;
use craft\app\elements\Entry;
use craft\app\models\EntryDraft;
use craft\app\models\EntryVersion;
use craft\app\models\Section;

/**
 * The EntriesController class is a controller that handles various entry related tasks such as retrieving, saving,
 * swapping between entry types, previewing, deleting and sharing entries.
 *
 * Note that all actions in the controller except [[actionViewSharedEntry]] require an authenticated Craft session
 * via [[Controller::allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class EntriesController extends BaseEntriesController
{
	// Properties
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	protected $allowAnonymous = ['view-shared-entry'];

	// Public Methods
	// =========================================================================

	/**
	 * Called when a user beings up an entry for editing before being displayed.
	 *
	 * @param string $sectionHandle The section’s handle
	 * @param int    $entryId       The entry’s ID, if editing an existing entry.
	 * @param int    $draftId       The entry draft’s ID, if editing an existing draft.
	 * @param int    $versionId     The entry version’s ID, if editing an existing version.
	 * @param int    $localeId      The locale ID, if specified.
	 * @param Entry  $entry         The entry being edited, if there were any validation errors.
	 * @return string The rendering result
	 * @throws HttpException
	 */
	public function actionEditEntry($sectionHandle, $entryId = null, $draftId = null, $versionId = null, $localeId = null, Entry $entry = null)
	{
		$variables = [
			'sectionHandle' => $sectionHandle,
			'entryId' => $entryId,
			'draftId' => $draftId,
			'versionId' => $versionId,
			'localeId' => $localeId,
			'entry' => $entry
		];

		$this->_prepEditEntryVariables($variables);

		/** @var Entry $entry */
		$entry = $variables['entry'];
		/** @var Section $section */
		$section = $variables['section'];

		// Make sure they have permission to edit this entry
		$this->enforceEditEntryPermissions($entry);

		$currentUser = Craft::$app->getUser()->getIdentity();

		$variables['permissionSuffix'] = ':'.$entry->sectionId;

		if (Craft::$app->getEdition() == Craft::Pro && $section->type != Section::TYPE_SINGLE)
		{
			// Author selector variables
			// ---------------------------------------------------------------------

			$variables['userElementType'] = User::className();

			$authorPermission = 'editEntries'.$variables['permissionSuffix'];

			$variables['authorOptionCriteria'] = [
				'can' => $authorPermission,
			];

			$variables['author'] = $entry->getAuthor();

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
			$section->type == Section::TYPE_STRUCTURE &&
			$section->maxLevels != 1
		)
		{
			$variables['elementType'] = Entry::className();

			$variables['parentOptionCriteria'] = [
				'locale'        => $variables['localeId'],
				'sectionId'     => $section->id,
				'status'        => null,
				'localeEnabled' => null,
			];

			if ($section->maxLevels)
			{
				$variables['parentOptionCriteria']['level'] = '< '.$section->maxLevels;
			}

			if ($entry->id)
			{
				// Prevent the current entry, or any of its descendants, from being options
				$excludeIds = Entry::find()
					->descendantOf($entry)
					->status(null)
					->localeEnabled(false)
					->ids();

				$excludeIds[] = $entry->id;
				$variables['parentOptionCriteria']['where'] = ['not in', 'elements.id', $excludeIds];
			}

			// Get the initially selected parent
			$parentId = Craft::$app->getRequest()->getParam('parentId');

			if ($parentId === null && $entry->id)
			{
				$parentIds = $entry->getAncestors(1)->status(null)->localeEnabled(false)->ids();

				if ($parentIds)
				{
					$parentId = $parentIds[0];
				}
			}

			if ($parentId)
			{
				$variables['parent'] = Craft::$app->getEntries()->getEntryById($parentId, $variables['localeId']);
			}
		}

		// Enabled locales
		// ---------------------------------------------------------------------

		if (Craft::$app->isLocalized())
		{
			if ($entry->id)
			{
				$variables['enabledLocales'] = Craft::$app->getElements()->getEnabledLocalesForElement($entry->id);
			}
			else
			{
				$variables['enabledLocales'] = [];

				foreach ($section->getLocales() as $locale)
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
			switch ($entry::className())
			{
				case EntryDraft::className():
				{
					/** @var EntryDraft $entry */
					$variables['revisionLabel'] = $entry->name;
					break;
				}

				case EntryVersion::className():
				{
					/** @var EntryVersion $entry */
					$variables['revisionLabel'] = Craft::t('app', 'Version {num}', ['num' => $entry->num]);
					break;
				}

				default:
				{
					$variables['revisionLabel'] = Craft::t('app', 'Current');
				}
			}
		}

		if (!$entry->id)
		{
			$variables['title'] = Craft::t('app', 'Create a new entry');
		}
		else
		{
			$variables['docTitle'] = Craft::t('app', $entry->title);
			$variables['title'] = Craft::t('app', $entry->title);

			if (Craft::$app->getEdition() >= Craft::Client && $entry::className() != Entry::className())
			{
				$variables['docTitle'] .= ' ('.$variables['revisionLabel'].')';
			}
		}

		// Breadcrumbs
		$variables['crumbs'] = [
			['label' => Craft::t('app', 'Entries'), 'url' => UrlHelper::getUrl('entries')]
		];

		if ($section->type == Section::TYPE_SINGLE)
		{
			$variables['crumbs'][] = ['label' => Craft::t('app', 'Singles'), 'url' => UrlHelper::getUrl('entries/singles')];
		}
		else
		{
			$variables['crumbs'][] = ['label' => Craft::t('app', $section->name), 'url' => UrlHelper::getUrl('entries/'.$section->handle)];

			if ($section->type == Section::TYPE_STRUCTURE)
			{
				/** @var Entry $ancestor */
				foreach ($entry->getAncestors() as $ancestor)
				{
					$variables['crumbs'][] = ['label' => $ancestor->title, 'url' => $ancestor->getCpEditUrl()];
				}
			}
		}

		// Multiple entry types?
		$entryTypes = $section->getEntryTypes();

		if (count($entryTypes) > 1)
		{
			$variables['showEntryTypes'] = true;

			foreach ($entryTypes as $entryType)
			{
				$variables['entryTypeOptions'][] = ['label' => Craft::t('app', $entryType->name), 'value' => $entryType->id];
			}

			Craft::$app->getView()->registerJsResource('js/EntryTypeSwitcher.js');
			Craft::$app->getView()->registerJs('new Craft.EntryTypeSwitcher();');
		}
		else
		{
			$variables['showEntryTypes'] = false;
		}

		// Enable Live Preview?
		if (!Craft::$app->getRequest()->getIsMobileBrowser(true) && Craft::$app->getSections()->isSectionTemplateValid($section))
		{
			Craft::$app->getView()->registerJs('Craft.LivePreview.init('.JsonHelper::encode([
				'fields'        => '#title-field, #fields > div > div > .field',
				'extraFields'   => '#settings',
				'previewUrl'    => $entry->getUrl(),
				'previewAction' => 'entries/preview-entry',
				'previewParams' => [
				                       'sectionId' => $section->id,
				                       'entryId'   => $entry->id,
				                       'locale'    => $entry->locale,
				                       'versionId' => ($entry::className() == EntryVersion::className() ? $entry->versionId : null),
				]
				]).');');

			$variables['showPreviewBtn'] = true;

			// Should we show the Share button too?
			if ($entry->id)
			{
				$className = $entry::className();

				// If we're looking at the live version of an entry, just use
				// the entry's main URL as its share URL
				if ($className == Entry::className() && $entry->getStatus() == Entry::STATUS_LIVE)
				{
					$variables['shareUrl'] = $entry->getUrl();
				}
				else
				{
					switch ($className)
					{
						case EntryDraft::className():
						{
							/** @var EntryDraft $entry */
							$shareParams = ['draftId' => $entry->draftId];
							break;
						}
						case EntryVersion::className():
						{
							/** @var EntryVersion $entry */
							$shareParams = ['versionId' => $entry->versionId];
							break;
						}
						default:
						{
							$shareParams = ['entryId' => $entry->id, 'locale' => $entry->locale];
							break;
						}
					}

					$variables['shareUrl'] = UrlHelper::getActionUrl('entries/share-entry', $shareParams);
				}
			}
		}
		else
		{
			$variables['showPreviewBtn'] = false;
		}

		// Set the base CP edit URL

		// Can't just use the entry's getCpEditUrl() because that might include the locale ID when we don't want it
		$variables['baseCpEditUrl'] = 'entries/'.$section->handle.'/{id}-{slug}';

		// Set the "Continue Editing" URL
		$variables['continueEditingUrl'] = $variables['baseCpEditUrl'] .
			(isset($variables['draftId']) ? '/drafts/'.$variables['draftId'] : '') .
			(Craft::$app->isLocalized() && Craft::$app->language != $variables['localeId'] ? '/'.$variables['localeId'] : '');

		// Can the user delete the entry?
		$variables['canDeleteEntry'] = $entry->id && (
			($entry->authorId == $currentUser->id && $currentUser->can('deleteEntries'.$variables['permissionSuffix'])) ||
			($entry->authorId != $currentUser->id && $currentUser->can('deletePeerEntries'.$variables['permissionSuffix']))
		);

		// Include translations
		Craft::$app->getView()->includeTranslations('Live Preview');

		// Render the template!
		Craft::$app->getView()->registerCssResource('css/entry.css');
		return $this->renderTemplate('entries/_edit', $variables);
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

		$paneHtml = Craft::$app->getView()->renderTemplate('_includes/tabs', $variables) .
			Craft::$app->getView()->renderTemplate('entries/_fields', $variables);

		return $this->asJson([
			'paneHtml' => $paneHtml,
			'headHtml' => Craft::$app->getView()->getHeadHtml(),
			'footHtml' => Craft::$app->getView()->getBodyEndHtml(true),
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
			$entry = Craft::$app->getEntryRevisions()->getVersionById($versionId);

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

			// Set the language to the user's preferred locale so DateFormatter returns the right format
			Craft::$app->language = Craft::$app->getTargetLanguage(true);

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
		$currentUser = Craft::$app->getUser()->getIdentity();

		if ($entry->id)
		{
			// Is this another user's entry (and it's not a Single)?
			if (
				$entry->authorId != $currentUser->id &&
				$entry->getSection()->type != Section::TYPE_SINGLE
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
		if (Craft::$app->getEntries()->saveEntry($entry))
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

				return $this->asJson($return);
			}
			else
			{
				Craft::$app->getSession()->setNotice(Craft::t('app', 'Entry saved.'));
				return $this->redirectToPostedUrl($entry);
			}
		}
		else
		{
			if (Craft::$app->getRequest()->getIsAjax())
			{
				return $this->asJson([
					'errors' => $entry->getErrors(),
				]);
			}
			else
			{
				Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t save entry.'));

				// Send the entry back to the template
				Craft::$app->getUrlManager()->setRouteParams([
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
		$entry = Craft::$app->getEntries()->getEntryById($entryId, $localeId);

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

		if (Craft::$app->getEntries()->deleteEntry($entry))
		{
			if (Craft::$app->getRequest()->getIsAjax())
			{
				return $this->asJson(['success' => true]);
			}
			else
			{
				Craft::$app->getSession()->setNotice(Craft::t('app', 'Entry deleted.'));
				return $this->redirectToPostedUrl($entry);
			}
		}
		else
		{
			if (Craft::$app->getRequest()->getIsAjax())
			{
				return $this->asJson(['success' => false]);
			}
			else
			{
				Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t delete entry.'));

				// Send the entry back to the template
				Craft::$app->getUrlManager()->setRouteParams([
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
			$entry = Craft::$app->getEntries()->getEntryById($entryId, $locale);

			if (!$entry)
			{
				throw new HttpException(404);
			}

			$params = ['entryId' => $entryId, 'locale' => $entry->locale];
		}
		else if ($draftId)
		{
			$entry = Craft::$app->getEntryRevisions()->getDraftById($draftId);

			if (!$entry)
			{
				throw new HttpException(404);
			}

			$params = ['draftId' => $draftId];
		}
		else if ($versionId)
		{
			$entry = Craft::$app->getEntryRevisions()->getVersionById($versionId);

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
		if (!Craft::$app->getSections()->isSectionTemplateValid($entry->getSection()))
		{
			throw new HttpException(404);
		}

		// Create the token and redirect to the entry URL with the token in place
		$token = Craft::$app->getTokens()->createToken(['action' => 'entries/view-shared-entry', 'params' => $params]);
		$url = UrlHelper::getUrlWithToken($entry->getUrl(), $token);
		return Craft::$app->getResponse()->redirect($url);
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
			$entry = Craft::$app->getEntries()->getEntryById($entryId, $locale);
		}
		else if ($draftId)
		{
			$entry = Craft::$app->getEntryRevisions()->getDraftById($draftId);
		}
		else if ($versionId)
		{
			$entry = Craft::$app->getEntryRevisions()->getVersionById($versionId);
		}

		if (empty($entry))
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
			$variables['section'] = Craft::$app->getSections()->getSectionByHandle($variables['sectionHandle']);
		}
		else if (!empty($variables['sectionId']))
		{
			$variables['section'] = Craft::$app->getSections()->getSectionById($variables['sectionId']);
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
					$variables['entry'] = Craft::$app->getEntryRevisions()->getDraftById($variables['draftId']);
				}
				else if (!empty($variables['versionId']))
				{
					$variables['entry'] = Craft::$app->getEntryRevisions()->getVersionById($variables['versionId']);
				}
				else
				{
					$variables['entry'] = Craft::$app->getEntries()->getEntryById($variables['entryId'], $variables['localeId']);
				}

				if (!$variables['entry'])
				{
					throw new HttpException(404);
				}
			}
			else
			{
				$variables['entry'] = new Entry();
				$variables['entry']->sectionId = $variables['section']->id;
				$variables['entry']->authorId = Craft::$app->getUser()->getIdentity()->id;
				$variables['entry']->enabled = true;

				if (!empty($variables['localeId']))
				{
					$variables['entry']->locale = $variables['localeId'];
				}

				if (Craft::$app->isLocalized())
				{
					// Set the default locale status based on the section's settings
					foreach ($variables['section']->getLocales() as $locale)
					{
						if ($locale->locale == $variables['entry']->locale)
						{
							$variables['entry']->localeEnabled = $locale->enabledByDefault;
							break;
						}
					}
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
					if ($variables['entry']->getErrors($field->handle))
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
	 * Fetches or creates an Entry.
	 *
	 * @throws Exception
	 * @return Entry
	 */
	private function _getEntryModel()
	{
		$entryId = Craft::$app->getRequest()->getBodyParam('entryId');
		$localeId = Craft::$app->getRequest()->getBodyParam('locale');

		if ($entryId)
		{
			$entry = Craft::$app->getEntries()->getEntryById($entryId, $localeId);

			if (!$entry)
			{
				throw new Exception(Craft::t('app', 'No entry exists with the ID “{id}”.', ['id' => $entryId]));
			}
		}
		else
		{
			$entry = new Entry();
			$entry->sectionId = Craft::$app->getRequest()->getRequiredBodyParam('sectionId');

			if ($localeId)
			{
				$entry->locale = $localeId;
			}
		}

		return $entry;
	}

	/**
	 * Populates an Entry with post data.
	 *
	 * @param Entry $entry
	 *
	 * @return null
	 */
	private function _populateEntryModel(Entry $entry)
	{
		// Set the entry attributes, defaulting to the existing values for whatever is missing from the post data
		$entry->typeId        = Craft::$app->getRequest()->getBodyParam('typeId', $entry->typeId);
		$entry->slug          = Craft::$app->getRequest()->getBodyParam('slug', $entry->slug);
		$entry->postDate      = (($postDate   = Craft::$app->getRequest()->getBodyParam('postDate')) ? DateTimeHelper::toDateTime($postDate, Craft::$app->getTimeZone()) : $entry->postDate);
		$entry->expiryDate    = (($expiryDate = Craft::$app->getRequest()->getBodyParam('expiryDate')) ? DateTimeHelper::toDateTime($expiryDate, Craft::$app->getTimeZone()) : null);
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
	 * @param Entry $entry
	 * @return string The rendering result
	 * @throws HttpException
	 */
	private function _showEntry(Entry $entry)
	{
		$section = $entry->getSection();
		$type = $entry->getType();

		if (!$section || !$type)
		{
			Craft::error('Attempting to preview an entry that doesn’t have a section/type.', __METHOD__);
			throw new HttpException(404);
		}

		Craft::$app->language = $entry->locale;

		if (!$entry->postDate)
		{
			$entry->postDate = new DateTime();
		}

		// Have this entry override any freshly queried entries with the same ID/locale
		Craft::$app->getElements()->setPlaceholderElement($entry);

		Craft::$app->getView()->getTwig()->disableStrictVariables();

		return $this->renderTemplate($section->template, [
			'entry' => $entry
		]);
	}
}
