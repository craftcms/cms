<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\controllers;

use craft\app\Craft;
use craft\app\enums\SectionType;
use craft\app\models\EntryDraft  as EntryDraftModel;
use craft\app\errors\Exception;

Craft::$app->requireEdition(Craft::Client);

/**
 * The EntryRevisionsController class is a controller that handles various entry version and draft related tasks such as
 * retrieving, saving, deleting, publishing and reverting entry drafts and versions.
 *
 * Note that all actions in the controller require an authenticated Craft session via [[BaseController::allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class EntryRevisionsController extends BaseEntriesController
{
	// Public Methods
	// =========================================================================

	/**
	 * Saves a draft, or creates a new one.
	 *
	 * @throws Exception
	 * @return null
	 */
	public function actionSaveDraft()
	{
		$this->requirePostRequest();

		$draftId = Craft::$app->request->getPost('draftId');

		if ($draftId)
		{
			$draft = Craft::$app->entryRevisions->getDraftById($draftId);

			if (!$draft)
			{
				throw new Exception(Craft::t('No draft exists with the ID “{id}”.', ['id' => $draftId]));
			}
		}
		else
		{
			$draft = new EntryDraftModel();
			$draft->id        = Craft::$app->request->getPost('entryId');
			$draft->sectionId = Craft::$app->request->getRequiredPost('sectionId');
			$draft->creatorId = Craft::$app->getUser()->getIdentity()->id;
			$draft->locale    = Craft::$app->request->getPost('locale', Craft::$app->i18n->getPrimarySiteLocaleId());
		}

		// Make sure they have permission to be editing this
		$this->enforceEditEntryPermissions($draft);

		$this->_setDraftAttributesFromPost($draft);

		if (!$draft->id)
		{
			// Attempt to create a new entry

			// Manually validate 'title' since the Elements service will just give it a title automatically.
			$fields = ['title'];
			$content = $draft->getContent();
			$content->setRequiredFields($fields);

			if ($content->validate($fields))
			{
				$draftEnabled = $draft->enabled;
				$draft->enabled = false;

				Craft::$app->entries->saveEntry($draft);

				$draft->enabled = $draftEnabled;
			}
			else
			{
				$draft->addErrors($content->getErrors());
			}
		}

		$fieldsLocation = Craft::$app->request->getParam('fieldsLocation', 'fields');
		$draft->setContentFromPost($fieldsLocation);

		if ($draft->id && Craft::$app->entryRevisions->saveDraft($draft))
		{
			Craft::$app->getSession()->setNotice(Craft::t('Draft saved.'));
			$this->redirectToPostedUrl($draft);
		}
		else
		{
			Craft::$app->getSession()->setError(Craft::t('Couldn’t save draft.'));

			// Send the draft back to the template
			Craft::$app->urlManager->setRouteVariables([
				'entry' => $draft
			]);
		}
	}

	/**
	 * Renames a draft.
	 *
	 * @throws Exception
	 * @return null
	 */
	public function actionUpdateDraftMeta()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$draftId = Craft::$app->request->getRequiredPost('draftId');
		$name = Craft::$app->request->getRequiredPost('name');

		$draft = Craft::$app->entryRevisions->getDraftById($draftId);

		if (!$draft)
		{
			throw new Exception(Craft::t('No draft exists with the ID “{id}”.', ['id' => $draftId]));
		}

		if ($draft->creatorId != Craft::$app->getUser()->getIdentity()->id)
		{
			// Make sure they have permission to be doing this
			$this->requirePermission('editPeerEntryDrafts:'.$draft->sectionId);
		}

		$draft->name = $name;
		$draft->revisionNotes = Craft::$app->request->getPost('notes');

		if (Craft::$app->entryRevisions->saveDraft($draft, false))
		{
			$this->returnJson(['success' => true]);
		}
		else
		{
			$this->returnErrorJson($draft->getError('name'));
		}
	}

	/**
	 * Deletes a draft.
	 *
	 * @throws Exception
	 * @return null
	 */
	public function actionDeleteDraft()
	{
		$this->requirePostRequest();

		$draftId = Craft::$app->request->getPost('draftId');
		$draft = Craft::$app->entryRevisions->getDraftById($draftId);

		if (!$draft)
		{
			throw new Exception(Craft::t('No draft exists with the ID “{id}”.', ['id' => $draftId]));
		}

		if ($draft->creatorId != Craft::$app->getUser()->getIdentity()->id)
		{
			$this->requirePermission('deletePeerEntryDrafts:'.$draft->sectionId);
		}

		Craft::$app->entryRevisions->deleteDraft($draft);

		$this->redirectToPostedUrl();
	}

	/**
	 * Publish a draft.
	 *
	 * @throws Exception
	 * @return null
	 */
	public function actionPublishDraft()
	{
		$this->requirePostRequest();

		$draftId = Craft::$app->request->getPost('draftId');
		$draft = Craft::$app->entryRevisions->getDraftById($draftId);
		$userId = Craft::$app->getUser()->getIdentity()->id;

		if (!$draft)
		{
			throw new Exception(Craft::t('No draft exists with the ID “{id}”.', ['id' => $draftId]));
		}

		// Permission enforcement
		$entry = Craft::$app->entries->getEntryById($draft->id, $draft->locale);

		if (!$entry)
		{
			throw new Exception(Craft::t('No entry exists with the ID “{id}”.', ['id' => $draft->id]));
		}

		$this->enforceEditEntryPermissions($entry);
		$userSessionService = Craft::$app->getUser();

		// Is this another user's entry (and it's not a Single)?
		if (
			$entry->authorId != $userSessionService->getUser()->id &&
			$entry->getSection()->type != SectionType::Single
		)
		{
			if ($entry->enabled)
			{
				// Make sure they have permission to make live changes to those
				$this->requirePermission('publishPeerEntries:'.$entry->sectionId);
			}
		}

		// Is this another user's draft?
		if ($draft->creatorId != $userId)
		{
			$this->requirePermission('publishPeerEntryDrafts:'.$entry->sectionId);
		}

		// Populate the main draft attributes
		$this->_setDraftAttributesFromPost($draft);

		// Even more permission enforcement
		if ($draft->enabled)
		{
			$this->requirePermission('publishEntries:'.$entry->sectionId);
		}

		// Populate the field content
		$fieldsLocation = Craft::$app->request->getParam('fieldsLocation', 'fields');
		$draft->setContentFromPost($fieldsLocation);

		// Publish the draft (finally!)
		if (Craft::$app->entryRevisions->publishDraft($draft))
		{
			Craft::$app->getSession()->setNotice(Craft::t('Draft published.'));
			$this->redirectToPostedUrl($draft);
		}
		else
		{
			Craft::$app->getSession()->setError(Craft::t('Couldn’t publish draft.'));

			// Send the draft back to the template
			Craft::$app->urlManager->setRouteVariables([
				'entry' => $draft
			]);
		}
	}

	/**
	 * Reverts an entry to a version.
	 *
	 * @throws Exception
	 * @return null
	 */
	public function actionRevertEntryToVersion()
	{
		$this->requirePostRequest();

		$versionId = Craft::$app->request->getPost('versionId');
		$version = Craft::$app->entryRevisions->getVersionById($versionId);

		if (!$version)
		{
			throw new Exception(Craft::t('No version exists with the ID “{id}”.', ['id' => $versionId]));
		}

		// Permission enforcement
		$entry = Craft::$app->entries->getEntryById($version->id, $version->locale);

		if (!$entry)
		{
			throw new Exception(Craft::t('No entry exists with the ID “{id}”.', ['id' => $version->id]));
		}

		$this->enforceEditEntryPermissions($entry);
		$userSessionService = Craft::$app->getUser();

		// Is this another user's entry (and it's not a Single)?
		if (
			$entry->authorId != $userSessionService->getUser()->id &&
			$entry->getSection()->type != SectionType::Single
		)
		{
			if ($entry->enabled)
			{
				// Make sure they have permission to make live changes to those
				$this->requirePermission('publishPeerEntries:'.$entry->sectionId);
			}
		}

		if ($entry->enabled)
		{
			$this->requirePermission('publishEntries:'.$entry->sectionId);
		}

		// Revert to the version
		if (Craft::$app->entryRevisions->revertEntryToVersion($version))
		{
			Craft::$app->getSession()->setNotice(Craft::t('Entry reverted to past version.'));
			$this->redirectToPostedUrl($version);
		}
		else
		{
			Craft::$app->getSession()->setError(Craft::t('Couldn’t revert entry to past version.'));

			// Send the version back to the template
			Craft::$app->urlManager->setRouteVariables([
				'entry' => $version
			]);
		}
	}

	// Private Methods
	// =========================================================================

	/**
	 * Sets a draft's attributes from the post data.
	 *
	 * @param EntryDraftModel $draft
	 *
	 * @return null
	 */
	private function _setDraftAttributesFromPost(EntryDraftModel $draft)
	{
		$draft->typeId     = Craft::$app->request->getPost('typeId');
		$draft->slug       = Craft::$app->request->getPost('slug');
		$draft->postDate   = Craft::$app->request->getPost('postDate');
		$draft->expiryDate = Craft::$app->request->getPost('expiryDate');
		$draft->enabled    = (bool) Craft::$app->request->getPost('enabled');
		$draft->getContent()->title = Craft::$app->request->getPost('title');

		// Author
		$authorId = Craft::$app->request->getPost('author', ($draft->authorId ? $draft->authorId : Craft::$app->getUser()->getIdentity()->id));

		if (is_array($authorId))
		{
			$authorId = isset($authorId[0]) ? $authorId[0] : null;
		}

		$draft->authorId = $authorId;

		// Parent
		$parentId = Craft::$app->request->getPost('parentId');

		if (is_array($parentId))
		{
			$parentId = isset($parentId[0]) ? $parentId[0] : null;
		}

		$draft->parentId = $parentId;
	}
}
