<?php
namespace Blocks;

Blocks::requirePackage(BlocksPackage::PublishPro);

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

		$draftId = blx()->request->getPost('draftId');

		if ($draftId)
		{
			$draft = blx()->entryRevisions->getDraftById($draftId);

			if (!$draft)
			{
				throw new Exception(Blocks::t('No draft exists with the ID “{id}”', array('id' => $draftId)));
			}
		}
		else
		{
			$draft = new EntryDraftModel();
			$draft->id = blx()->request->getRequiredPost('entryId');
			$draft->sectionId = blx()->request->getRequiredPost('sectionId');
			$draft->creatorId = blx()->userSession->getUser()->id;
			$draft->locale    = blx()->request->getPost('locale', blx()->i18n->getPrimarySiteLocale()->getId());
		}

		$this->_setDraftValuesFromPost($draft);

		if (blx()->entryRevisions->saveDraft($draft))
		{
			blx()->userSession->setNotice(Blocks::t('Draft saved.'));

			$this->redirectToPostedUrl(array(
				'entryId' => $draft->id,
				'draftId' => $draft->draftId
			));
		}
		else
		{
			blx()->userSession->setError(Blocks::t('Couldn’t save draft.'));

			$this->renderRequestedTemplate(array(
				'entry' => $draft
			));
		}
	}

	/**
	 * Publishes a draft.
	 */
	public function actionPublishDraft()
	{
		$this->requirePostRequest();

		$draftId = blx()->request->getPost('draftId');
		$draft = blx()->entryRevisions->getDraftById($draftId);

		if (!$draft)
		{
			throw new Exception(Blocks::t('No draft exists with the ID “{id}”', array('id' => $draftId)));
		}

		$this->_setDraftValuesFromPost($draft);

		if (blx()->entryRevisions->publishDraft($draft))
		{
			blx()->userSession->setNotice(Blocks::t('Draft published.'));

			$this->redirectToPostedUrl(array(
				'entryId' => $draft->id
			));
		}
		else
		{
			blx()->userSession->setError(Blocks::t('Couldn’t publish draft.'));

			$this->renderRequestedTemplate(array(
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
		$draft->title = blx()->request->getPost('title');
		$draft->slug = blx()->request->getPost('slug');
		$draft->postDate = $this->getDateFromPost('postDate');
		$draft->expiryDate = $this->getDateFromPost('expiryDate');
		$draft->enabled = blx()->request->getPost('enabled');
		$draft->tags = blx()->request->getPost('tags');

		$draft->setContent(blx()->request->getPost('blocks'));

		if (Blocks::hasPackage(BlocksPackage::Users))
		{
			$draft->authorId = blx()->request->getPost('author');
		}
		else
		{
			$draft->authorId = blx()->userSession->getUser()->id;
		}
	}
}
