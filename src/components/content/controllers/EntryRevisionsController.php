<?php
namespace Blocks;

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
			$draft->creatorId = blx()->account->getCurrentUser()->id;

			//if (Blocks::hasPackage(BlocksPackage::Language))
			//{
			//	$draft->language = blx()->request->getPost('language');
			//}
			//else
			//{
				$draft->language = blx()->language;
			//}
		}

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
			$draft->authorId = blx()->account->getCurrentUser()->id;
		}

		if (blx()->entryRevisions->saveDraft($draft))
		{
			blx()->user->setNotice(Blocks::t('Draft saved.'));

			$this->redirectToPostedUrl(array(
				'entryId' => $draft->id,
				'draftId' => $draft->draftId
			));
		}
		else
		{
			blx()->user->setError(Blocks::t('Couldn’t save draft.'));

			$this->renderRequestedTemplate(array(
				'entry' => $draft
			));
		}
	}
}
