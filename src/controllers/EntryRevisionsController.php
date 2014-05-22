<?php
namespace Craft;

craft()->requireEdition(Craft::Client);

/**
 *
 */
class EntryRevisionsController extends BaseController
{
	/**
	 * Saves a draft, or creates a new one.
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
				throw new Exception(Craft::t('No draft exists with the ID “{id}”', array('id' => $draftId)));
			}
		}
		else
		{
			$draft = new EntryDraftModel();
			$draft->id        = craft()->request->getRequiredPost('entryId');
			$draft->sectionId = craft()->request->getRequiredPost('sectionId');
			$draft->creatorId = craft()->userSession->getUser()->id;
			$draft->locale    = craft()->request->getPost('locale', craft()->i18n->getPrimarySiteLocaleId());
		}

		$this->_setRevisionAttributesFromPost($draft);

		if (craft()->entryRevisions->saveDraft($draft))
		{
			craft()->userSession->setNotice(Craft::t('Draft saved.'));

			if (isset($_POST['redirect']) && mb_strpos($_POST['redirect'], '{entryId}') !== false)
			{
				craft()->deprecator->log('EntryRevisionsController::saveDraft():entryId_redirect', 'The {entryId} token within the ‘redirect’ param on entryRevisions/saveDraft requests has been deprecated. Use {id} instead.');
				$_POST['redirect'] = str_replace('{entryId}', '{id}', $_POST['redirect']);
			}

			$this->redirectToPostedUrl($draft);
		}
		else
		{
			craft()->userSession->setError(Craft::t('Couldn’t save draft.'));

			// Send the draft back to the template
			craft()->urlManager->setRouteVariables(array(
				'entry' => $draft
			));
		}
	}

	/**
	 * Renames a draft.
	 */
	public function actionRenameDraft()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$draftId = craft()->request->getRequiredPost('draftId');
		$name = craft()->request->getRequiredPost('name');

		$draft = craft()->entryRevisions->getDraftById($draftId);

		if (!$draft)
		{
			throw new Exception(Craft::t('No draft exists with the ID “{id}”', array('id' => $draftId)));
		}

		if ($draft->creatorId != craft()->userSession->getUser()->id)
		{
			// Make sure they have permission to be doing this
			craft()->userSession->requirePermission('editPeerEntryDrafts:'.$draft->sectionId);
		}

		$draft->name = $name;

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
	 */
	public function actionDeleteDraft()
	{
		$this->requirePostRequest();

		$draftId = craft()->request->getPost('draftId');
		$draft = craft()->entryRevisions->getDraftById($draftId);

		if (!$draft)
		{
			throw new Exception(Craft::t('No draft exists with the ID “{id}”', array('id' => $draftId)));
		}

		if ($draft->creatorId != craft()->userSession->getUser()->id)
		{
			craft()->userSession->requirePermission('deletePeerEntryDrafts:'.$draft->sectionId);
		}

		craft()->entryRevisions->deleteDraft($draft);

		$this->redirectToPostedUrl();
	}

	/**
	 * Publish a draft.
	 */
	public function actionPublishDraft()
	{
		$this->requirePostRequest();

		$draftId = craft()->request->getPost('draftId');
		$draft = craft()->entryRevisions->getDraftById($draftId);
		$userId = craft()->userSession->getUser()->id;

		if (!$draft)
		{
			throw new Exception(Craft::t('No draft exists with the ID “{id}”', array('id' => $draftId)));
		}

		$entry = craft()->entries->getEntryById($draft->id);

		if (!$entry)
		{
			throw new Exception(Craft::t('No entry exists with the ID “{id}”', array('id' => $entry->id)));
		}

		// Make sure they are allowed to publish entries in this section
		craft()->userSession->requirePermission('publishEntries:'.$entry->sectionId);

		// Is this another user's entry (and it's not a Single)?
		if (
			$entry->authorId != $userId &&
			$entry->getSection()->type != SectionType::Single
		)
		{
			craft()->userSession->requirePermission('publishPeerEntries:'.$entry->sectionId);
		}

		// Is this another user's draft?
		if ($draft->creatorId != $userId)
		{
			craft()->userSession->requirePermission('publishPeerEntryDrafts:'.$entry->sectionId);
		}

		$this->_setRevisionAttributesFromPost($draft);

		if (craft()->entryRevisions->publishDraft($draft))
		{
			craft()->userSession->setNotice(Craft::t('Draft published.'));

			if (isset($_POST['redirect']) && mb_strpos($_POST['redirect'], '{entryId}') !== false)
			{
				craft()->deprecator->log('EntryRevisionsController::publishDraft():entryId_redirect', 'The {entryId} token within the ‘redirect’ param on entryRevisions/publishDraft requests has been deprecated. Use {id} instead.');
				$_POST['redirect'] = str_replace('{entryId}', '{id}', $_POST['redirect']);
			}

			$this->redirectToPostedUrl($draft);
		}
		else
		{
			craft()->userSession->setError(Craft::t('Couldn’t publish draft.'));

			// Send the draft back to the template
			craft()->urlManager->setRouteVariables(array(
				'entry' => $draft
			));
		}
	}

	/**
	 * Reverts an entry to a version.
	 */
	public function actionRevertEntryToVersion()
	{
		$this->requirePostRequest();

		$versionId = craft()->request->getPost('versionId');
		$version = craft()->entryRevisions->getVersionById($versionId);
		$userId = craft()->userSession->getUser()->id;

		if (!$version)
		{
			throw new Exception(Craft::t('No version exists with the ID “{id}”', array('id' => $versionId)));
		}

		$entry = craft()->entries->getEntryById($version->id);

		if (!$entry)
		{
			throw new Exception(Craft::t('No entry exists with the ID “{id}”', array('id' => $entry->id)));
		}

		// Make sure they are allowed to publish entries in this section
		craft()->userSession->requirePermission('publishEntries:'.$entry->sectionId);

		// Is this another user's entry (and it's not a Single)?
		if (
			$entry->authorId != $userId &&
			$entry->getSection()->type != SectionType::Single
		)
		{
			craft()->userSession->requirePermission('publishPeerEntries:'.$entry->sectionId);
		}

		$this->_setRevisionAttributesFromPost($version);

		if (craft()->entryRevisions->revertEntryToVersion($version))
		{
			craft()->userSession->setNotice(Craft::t('Entry reverted to past version.'));
			$this->redirectToPostedUrl($version);
		}
		else
		{
			craft()->userSession->setError(Craft::t('Couldn’t revert entry to past version.'));

			// Send the version back to the template
			craft()->urlManager->setRouteVariables(array(
				'entry' => $version
			));
		}
	}

	/**
	 * Sets the revision model's attributes from the post data.
	 *
	 * @access private
	 * @param BaseEntryRevisionModel $revision
	 */
	private function _setRevisionAttributesFromPost(BaseEntryRevisionModel $revision)
	{
		$revision->slug       = craft()->request->getPost('slug');
		$revision->postDate   = craft()->request->getPost('postDate');
		$revision->expiryDate = craft()->request->getPost('expiryDate');
		$revision->enabled    = (bool) craft()->request->getPost('enabled');
		$revision->authorId   = craft()->request->getPost('author');

		$revision->getContent()->title = craft()->request->getPost('title');

		$fieldsLocation = craft()->request->getParam('fieldsLocation', 'fields');
		$revision->setContentFromPost($fieldsLocation);
	}
}
