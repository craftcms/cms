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

craft()->requireEdition(Craft::Client);

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

		$draftId = craft()->request->getPost('draftId');

		if ($draftId)
		{
			$draft = craft()->entryRevisions->getDraftById($draftId);

			if (!$draft)
			{
				throw new Exception(Craft::t('No draft exists with the ID “{id}”.', array('id' => $draftId)));
			}
		}
		else
		{
			$draft = new EntryDraftModel();
			$draft->id        = craft()->request->getPost('entryId');
			$draft->sectionId = craft()->request->getRequiredPost('sectionId');
			$draft->creatorId = craft()->getUser()->getIdentity()->id;
			$draft->locale    = craft()->request->getPost('locale', craft()->i18n->getPrimarySiteLocaleId());
		}

		// Make sure they have permission to be editing this
		$this->enforceEditEntryPermissions($draft);

		$this->_setDraftAttributesFromPost($draft);

		if (!$draft->id)
		{
			// Attempt to create a new entry

			// Manually validate 'title' since the Elements service will just give it a title automatically.
			$fields = array('title');
			$content = $draft->getContent();
			$content->setRequiredFields($fields);

			if ($content->validate($fields))
			{
				$draftEnabled = $draft->enabled;
				$draft->enabled = false;

				craft()->entries->saveEntry($draft);

				$draft->enabled = $draftEnabled;
			}
			else
			{
				$draft->addErrors($content->getErrors());
			}
		}

		$fieldsLocation = craft()->request->getParam('fieldsLocation', 'fields');
		$draft->setContentFromPost($fieldsLocation);

		if ($draft->id && craft()->entryRevisions->saveDraft($draft))
		{
			craft()->getSession()->setNotice(Craft::t('Draft saved.'));
			$this->redirectToPostedUrl($draft);
		}
		else
		{
			craft()->getSession()->setError(Craft::t('Couldn’t save draft.'));

			// Send the draft back to the template
			craft()->urlManager->setRouteVariables(array(
				'entry' => $draft
			));
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

		$draftId = craft()->request->getRequiredPost('draftId');
		$name = craft()->request->getRequiredPost('name');

		$draft = craft()->entryRevisions->getDraftById($draftId);

		if (!$draft)
		{
			throw new Exception(Craft::t('No draft exists with the ID “{id}”.', array('id' => $draftId)));
		}

		if ($draft->creatorId != craft()->getUser()->getIdentity()->id)
		{
			// Make sure they have permission to be doing this
			$this->requirePermission('editPeerEntryDrafts:'.$draft->sectionId);
		}

		$draft->name = $name;
		$draft->revisionNotes = craft()->request->getPost('notes');

		if (craft()->entryRevisions->saveDraft($draft, false))
		{
			$this->returnJson(array('success' => true));
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

		$draftId = craft()->request->getPost('draftId');
		$draft = craft()->entryRevisions->getDraftById($draftId);

		if (!$draft)
		{
			throw new Exception(Craft::t('No draft exists with the ID “{id}”.', array('id' => $draftId)));
		}

		if ($draft->creatorId != craft()->getUser()->getIdentity()->id)
		{
			$this->requirePermission('deletePeerEntryDrafts:'.$draft->sectionId);
		}

		craft()->entryRevisions->deleteDraft($draft);

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

		$draftId = craft()->request->getPost('draftId');
		$draft = craft()->entryRevisions->getDraftById($draftId);
		$userId = craft()->getUser()->getIdentity()->id;

		if (!$draft)
		{
			throw new Exception(Craft::t('No draft exists with the ID “{id}”.', array('id' => $draftId)));
		}

		// Permission enforcement
		$entry = craft()->entries->getEntryById($draft->id, $draft->locale);

		if (!$entry)
		{
			throw new Exception(Craft::t('No entry exists with the ID “{id}”.', array('id' => $draft->id)));
		}

		$this->enforceEditEntryPermissions($entry);
		$userSessionService = craft()->getUser();

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
		$fieldsLocation = craft()->request->getParam('fieldsLocation', 'fields');
		$draft->setContentFromPost($fieldsLocation);

		// Publish the draft (finally!)
		if (craft()->entryRevisions->publishDraft($draft))
		{
			craft()->getSession()->setNotice(Craft::t('Draft published.'));
			$this->redirectToPostedUrl($draft);
		}
		else
		{
			craft()->getSession()->setError(Craft::t('Couldn’t publish draft.'));

			// Send the draft back to the template
			craft()->urlManager->setRouteVariables(array(
				'entry' => $draft
			));
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

		$versionId = craft()->request->getPost('versionId');
		$version = craft()->entryRevisions->getVersionById($versionId);

		if (!$version)
		{
			throw new Exception(Craft::t('No version exists with the ID “{id}”.', array('id' => $versionId)));
		}

		// Permission enforcement
		$entry = craft()->entries->getEntryById($version->id, $version->locale);

		if (!$entry)
		{
			throw new Exception(Craft::t('No entry exists with the ID “{id}”.', array('id' => $version->id)));
		}

		$this->enforceEditEntryPermissions($entry);
		$userSessionService = craft()->getUser();

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
		if (craft()->entryRevisions->revertEntryToVersion($version))
		{
			craft()->getSession()->setNotice(Craft::t('Entry reverted to past version.'));
			$this->redirectToPostedUrl($version);
		}
		else
		{
			craft()->getSession()->setError(Craft::t('Couldn’t revert entry to past version.'));

			// Send the version back to the template
			craft()->urlManager->setRouteVariables(array(
				'entry' => $version
			));
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
		$draft->typeId     = craft()->request->getPost('typeId');
		$draft->slug       = craft()->request->getPost('slug');
		$draft->postDate   = craft()->request->getPost('postDate');
		$draft->expiryDate = craft()->request->getPost('expiryDate');
		$draft->enabled    = (bool) craft()->request->getPost('enabled');
		$draft->getContent()->title = craft()->request->getPost('title');

		// Author
		$authorId = craft()->request->getPost('author', ($draft->authorId ? $draft->authorId : craft()->getUser()->getIdentity()->id));

		if (is_array($authorId))
		{
			$authorId = isset($authorId[0]) ? $authorId[0] : null;
		}

		$draft->authorId = $authorId;

		// Parent
		$parentId = craft()->request->getPost('parentId');

		if (is_array($parentId))
		{
			$parentId = isset($parentId[0]) ? $parentId[0] : null;
		}

		$draft->parentId = $parentId;
	}
}
