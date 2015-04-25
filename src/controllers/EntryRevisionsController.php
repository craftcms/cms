<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\controllers;

use Craft;
use craft\app\errors\Exception;
use craft\app\models\EntryDraft as EntryDraftModel;
use craft\app\models\Section;

Craft::$app->requireEdition(Craft::Client);

/**
 * The EntryRevisionsController class is a controller that handles various entry version and draft related tasks such as
 * retrieving, saving, deleting, publishing and reverting entry drafts and versions.
 *
 * Note that all actions in the controller require an authenticated Craft session via [[Controller::allowAnonymous]].
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

		$draftId = Craft::$app->getRequest()->getBodyParam('draftId');

		if ($draftId)
		{
			$draft = Craft::$app->getEntryRevisions()->getDraftById($draftId);

			if (!$draft)
			{
				throw new Exception(Craft::t('app', 'No draft exists with the ID “{id}”.', ['id' => $draftId]));
			}
		}
		else
		{
			$draft = new EntryDraftModel();
			$draft->id        = Craft::$app->getRequest()->getBodyParam('entryId');
			$draft->sectionId = Craft::$app->getRequest()->getRequiredBodyParam('sectionId');
			$draft->creatorId = Craft::$app->getUser()->getIdentity()->id;
			$draft->locale    = Craft::$app->getRequest()->getBodyParam('locale', Craft::$app->getI18n()->getPrimarySiteLocaleId());
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

				Craft::$app->getEntries()->saveEntry($draft);

				$draft->enabled = $draftEnabled;
			}
			else
			{
				$draft->addErrors($content->getErrors());
			}
		}

		$fieldsLocation = Craft::$app->getRequest()->getParam('fieldsLocation', 'fields');
		$draft->setContentFromPost($fieldsLocation);

		if ($draft->id && Craft::$app->getEntryRevisions()->saveDraft($draft))
		{
			Craft::$app->getSession()->setNotice(Craft::t('app', 'Draft saved.'));
			return $this->redirectToPostedUrl($draft);
		}
		else
		{
			Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t save draft.'));

			// Send the draft back to the template
			Craft::$app->getUrlManager()->setRouteParams([
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

		$draftId = Craft::$app->getRequest()->getRequiredBodyParam('draftId');
		$name = Craft::$app->getRequest()->getRequiredBodyParam('name');

		$draft = Craft::$app->getEntryRevisions()->getDraftById($draftId);

		if (!$draft)
		{
			throw new Exception(Craft::t('app', 'No draft exists with the ID “{id}”.', ['id' => $draftId]));
		}

		if ($draft->creatorId != Craft::$app->getUser()->getIdentity()->id)
		{
			// Make sure they have permission to be doing this
			$this->requirePermission('editPeerEntryDrafts:'.$draft->sectionId);
		}

		$draft->name = $name;
		$draft->revisionNotes = Craft::$app->getRequest()->getBodyParam('notes');

		if (Craft::$app->getEntryRevisions()->saveDraft($draft, false))
		{
			return $this->asJson(['success' => true]);
		}
		else
		{
			return $this->asErrorJson($draft->getFirstError('name'));
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

		$draftId = Craft::$app->getRequest()->getBodyParam('draftId');
		$draft = Craft::$app->getEntryRevisions()->getDraftById($draftId);

		if (!$draft)
		{
			throw new Exception(Craft::t('app', 'No draft exists with the ID “{id}”.', ['id' => $draftId]));
		}

		if ($draft->creatorId != Craft::$app->getUser()->getIdentity()->id)
		{
			$this->requirePermission('deletePeerEntryDrafts:'.$draft->sectionId);
		}

		Craft::$app->getEntryRevisions()->deleteDraft($draft);

		return $this->redirectToPostedUrl();
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

		$draftId = Craft::$app->getRequest()->getBodyParam('draftId');
		$draft = Craft::$app->getEntryRevisions()->getDraftById($draftId);
		$userId = Craft::$app->getUser()->getIdentity()->id;

		if (!$draft)
		{
			throw new Exception(Craft::t('app', 'No draft exists with the ID “{id}”.', ['id' => $draftId]));
		}

		// Permission enforcement
		$entry = Craft::$app->getEntries()->getEntryById($draft->id, $draft->locale);

		if (!$entry)
		{
			throw new Exception(Craft::t('app', 'No entry exists with the ID “{id}”.', ['id' => $draft->id]));
		}

		$this->enforceEditEntryPermissions($entry);
		$userSessionService = Craft::$app->getUser();

		// Is this another user's entry (and it's not a Single)?
		if (
			$entry->authorId != $userSessionService->getIdentity()->id &&
			$entry->getSection()->type != Section::TYPE_SINGLE
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
		$fieldsLocation = Craft::$app->getRequest()->getParam('fieldsLocation', 'fields');
		$draft->setContentFromPost($fieldsLocation);

		// Publish the draft (finally!)
		if (Craft::$app->getEntryRevisions()->publishDraft($draft))
		{
			Craft::$app->getSession()->setNotice(Craft::t('app', 'Draft published.'));
			return $this->redirectToPostedUrl($draft);
		}
		else
		{
			Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t publish draft.'));

			// Send the draft back to the template
			Craft::$app->getUrlManager()->setRouteParams([
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

		$versionId = Craft::$app->getRequest()->getBodyParam('versionId');
		$version = Craft::$app->getEntryRevisions()->getVersionById($versionId);

		if (!$version)
		{
			throw new Exception(Craft::t('app', 'No version exists with the ID “{id}”.', ['id' => $versionId]));
		}

		// Permission enforcement
		$entry = Craft::$app->getEntries()->getEntryById($version->id, $version->locale);

		if (!$entry)
		{
			throw new Exception(Craft::t('app', 'No entry exists with the ID “{id}”.', ['id' => $version->id]));
		}

		$this->enforceEditEntryPermissions($entry);
		$userSessionService = Craft::$app->getUser();

		// Is this another user's entry (and it's not a Single)?
		if (
			$entry->authorId != $userSessionService->getIdentity()->id &&
			$entry->getSection()->type != Section::TYPE_SINGLE
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
		if (Craft::$app->getEntryRevisions()->revertEntryToVersion($version))
		{
			Craft::$app->getSession()->setNotice(Craft::t('app', 'Entry reverted to past version.'));
			return $this->redirectToPostedUrl($version);
		}
		else
		{
			Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t revert entry to past version.'));

			// Send the version back to the template
			Craft::$app->getUrlManager()->setRouteParams([
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
		$draft->typeId     = Craft::$app->getRequest()->getBodyParam('typeId');
		$draft->slug       = Craft::$app->getRequest()->getBodyParam('slug');
		$draft->postDate   = Craft::$app->getRequest()->getBodyParam('postDate');
		$draft->expiryDate = Craft::$app->getRequest()->getBodyParam('expiryDate');
		$draft->enabled    = (bool) Craft::$app->getRequest()->getBodyParam('enabled');
		$draft->getContent()->title = Craft::$app->getRequest()->getBodyParam('title');

		// Author
		$authorId = Craft::$app->getRequest()->getBodyParam('author', ($draft->authorId ? $draft->authorId : Craft::$app->getUser()->getIdentity()->id));

		if (is_array($authorId))
		{
			$authorId = isset($authorId[0]) ? $authorId[0] : null;
		}

		$draft->authorId = $authorId;

		// Parent
		$parentId = Craft::$app->getRequest()->getBodyParam('parentId');

		if (is_array($parentId))
		{
			$parentId = isset($parentId[0]) ? $parentId[0] : null;
		}

		$draft->newParentId = $parentId;
	}
}
