<?php
namespace Craft;

Craft::requirePackage(CraftPackage::PublishPro);

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

		$this->_setDraftValuesFromPost($draft);

		if (craft()->entryRevisions->saveDraft($draft))
		{
			craft()->userSession->setNotice(Craft::t('Draft saved.'));

			// TODO: Remove for 2.0
			if (isset($_POST['redirect']) && strpos($_POST['redirect'], '{entryId}') !== false)
			{
				Craft::log('The {entryId} token within the ‘redirect’ param on entryRevisions/saveDraft requests has been deprecated. Use {id} instead.', LogLevel::Warning);
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

		if (!$draft)
		{
			throw new Exception(Craft::t('No draft exists with the ID “{id}”', array('id' => $draftId)));
		}

		if ($draft->creatorId != craft()->userSession->getUser()->id)
		{
			craft()->userSession->requirePermission('publishPeerEntryDrafts:'.$draft->sectionId);
		}

		$this->_setDraftValuesFromPost($draft);

		if (craft()->entryRevisions->publishDraft($draft))
		{
			craft()->userSession->setNotice(Craft::t('Draft published.'));

			// TODO: Remove for 2.0
			if (isset($_POST['redirect']) && strpos($_POST['redirect'], '{entryId}') !== false)
			{
				Craft::log('The {entryId} token within the ‘redirect’ param on entryRevisions/publishDraft requests has been deprecated. Use {id} instead.', LogLevel::Warning);
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
	 * Sets the draft model's values from the post data.
	 *
	 * @access private
	 * @param EntryDraftModel $draft
	 */
	private function _setDraftValuesFromPost(EntryDraftModel $draft)
	{
		$draft->title      = craft()->request->getPost('title');
		$draft->slug       = craft()->request->getPost('slug');
		$draft->postDate   = craft()->request->getPost('postDate');
		$draft->expiryDate = craft()->request->getPost('expiryDate');
		$draft->enabled    = (bool)craft()->request->getPost('enabled');
		$draft->tags       = craft()->request->getPost('tags');

		$fields = craft()->request->getPost('fields');
		$draft->getContent()->setAttributes($fields);

		if (Craft::hasPackage(CraftPackage::Users))
		{
			$draft->authorId = craft()->request->getPost('author');
		}
		else
		{
			$draft->authorId = craft()->userSession->getUser()->id;
		}
	}
}
