<?php
namespace Blocks;

/**
 * Handles content management tasks
 */
class ContentController extends BaseController
{
	/* BLOCKSPRO ONLY */
	// -------------------------------------------
	//  Sections
	// -------------------------------------------

	/* Sections */

	/**
	 * Saves a section
	 */
	public function actionSaveSection()
	{
		$this->requirePostRequest();

		$sectionPackage = new SectionPackage();
		$sectionPackage->id = blx()->request->getPost('sectionId');
		$sectionPackage->name = blx()->request->getPost('name');
		$sectionPackage->handle = blx()->request->getPost('handle');
		$sectionPackage->hasUrls = blx()->request->getPost('hasUrls');
		$sectionPackage->urlFormat = blx()->request->getPost('urlFormat');
		$sectionPackage->template = blx()->request->getPost('template');

		if ($sectionPackage->save())
		{
			blx()->user->setNotice(Blocks::t('Section saved.'));
			$this->redirectToPostedUrl(array(
				'sectionId' => $sectionPackage->id
			));
		}
		else
		{
			blx()->user->setError(Blocks::t('Couldn’t save section.'));
		}

		// Reload the original template
		$this->renderRequestedTemplate(array(
			'sectionPackage' => $sectionPackage
		));
	}

	/**
	 * Deletes a section.
	 */
	public function actionDeleteSection()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$sectionId = blx()->request->getRequiredPost('sectionId');

		blx()->content->deleteSectionById($sectionId);
		$this->returnJson(array('success' => true));
	}

	/* end BLOCKSPRO ONLY */

	// -------------------------------------------
	//  Entries
	// -------------------------------------------

	/**
	 * Saves an entry.
	 */
	public function actionSaveEntry()
	{
		$this->requirePostRequest();

		$entryPackage = new EntryPackage();
		$this->_populateEntryPackageFromPost($entryPackage);

		if ($entryPackage->save())
		{
			blx()->user->setNotice(Blocks::t('Entry saved.'));

			// Do we need to delete a draft?
			if (($draftId = blx()->request->getPost('draftId')) !== null)
			{
				blx()->content->deleteEntryDraftById($draftId);
			}

			$this->redirectToPostedUrl(array(
				'entryId' => $entryPackage->id
			));
		}
		else
		{
			blx()->user->setError(Blocks::t('Couldn’t save entry.'));
		}

		$this->renderRequestedTemplate(array(
			'entry' => $entryPackage
		));
	}

	/**
	 * Saves an entry draft.
	 */
	public function actionSaveEntryDraft()
	{
		$draftPackage = new EntryDraftPackage();
		$this->_populateEntryPackageFromPost($draftPackage);
		$draftPackage->draftId = blx()->request->getPost('draftId');

		if ($draftPackage->save())
		{
			blx()->user->setNotice(Blocks::t('Draft saved.'));

			$this->redirectToPostedUrl(array(
				'entryId' => $draftPackage->id,
				'draftId' => $draftPackage->draftId
			));
		}
		else
		{
			blx()->user->setError(Blocks::t('Couldn’t save draft.'));
		}

		$this->renderRequestedTemplate(array(
			'entry' => $draftPackage
		));
	}

	/**
	 * Populates an entry package from post data.
	 *
	 * @param EntryPackage $entryPackage
	 */
	private function _populateEntryPackageFromPost(EntryPackage $entryPackage)
	{
		$entryPackage->id = blx()->request->getPost('entryId');
		/* BLOCKSPRO ONLY */
		$entryPackage->authorId = blx()->accounts->getCurrentUser()->id;
		$entryPackage->sectionId = blx()->request->getRequiredPost('sectionId');
		$entryPackage->language = blx()->request->getPost('language');
		/* end BLOCKSPRO ONLY */
		$entryPackage->title = blx()->request->getPost('title');
		$entryPackage->slug = blx()->request->getPost('slug');

		if ($postDate = blx()->request->getPost('postDate'))
		{
			$entryPackage->postDate = DateTime::createFromFormat(DateTime::W3C_DATE, $postDate);
		}

		/* BLOCKSPRO ONLY */
		if ($expiryDate = blx()->request->getPost('expiryDate'))
		{
			$entryPackage->expiryDate = DateTime::createFromFormat(DateTime::W3C_DATE, $expiryDate);
		}

		/* end BLOCKSPRO ONLY */
		$entryPackage->blocks = blx()->request->getPost('blocks');
	}

	/**
	 * Creates a new draft.
	 */
	public function actionCreateDraft()
	{
		$this->requirePostRequest();

		$entry = $this->_getEntry();
		$changes = $this->_getContentFromPost($entry);
		$draftName = blx()->request->getPost('draftName');

		// Create the new draft
		$draft = blx()->content->createEntryDraft($entry, $changes, $draftName);

		blx()->user->setNotice(Blocks::t('Draft created.'));
		$this->redirect("content/{$entry->id}/draft{$draft->num}");
	}

	/**
	 * Publishes a draft
	 */
	public function actionPublishDraft()
	{
		$this->requirePostRequest();

		$entry = $this->_getEntry();
		$draft = $this->_getDraft();
		$changes = $this->_getContentFromPost($entry);

		// Save the new changes
		if ($changes)
			blx()->content->saveDraftContent($draft, $changes);

		// Publish the draft
		$draft->entry = $entry;
		if (blx()->content->publishEntryDraft($draft))
		{
			blx()->user->setNotice(Blocks::t('Draft published.'));
			$this->redirect('content/'.$entry->id);
		}
		else
		{
			blx()->user->setError(Blocks::t('Couldn’t publish draft.'));
			$entry->setDraft($draft);
			$this->renderRequestedTemplate(array(
				'entry' => $entry
			));
		}
	}

	/**
	 * Returns an entry based on the entryId in the post data
	 *
	 * @access private
	 * @throws Exception
	 * @return Entry
	 */
	private function _getEntry()
	{
		$entryId = blx()->request->getRequiredPost('entryId');
		$entry = blx()->content->getEntryById($entryId);

		if (!$entry)
			throw new Exception(Blocks::t('No entry exists with the ID “{id}”.', array('id' => $entryId)));

		return $entry;
	}

	/**
	 * Returns a draft based on the draftId in the post data
	 * @access private
	 * @throws Exception
	 * @return EntryVersion
	 */
	private function _getDraft()
	{
		$draftId = blx()->request->getRequiredPost('draftId');
		$draft = blx()->content->getDraftById($draftId);

		if (!$draft)
			throw new Exception(Blocks::t('No draft exists with the ID “{id}”.', array('id' => $draft)));

		return $draft;
	}

	/**
	 * Returns any content changes in the post data
	 *
	 * @access private
	 * @param  Entry $entry
	 * @return array
	 */
	private function _getContentFromPost($entry)
	{
		$changes = array();

		if (($title = blx()->request->getPost('title')) !== null)
			$changes['title'] = $title;

		foreach ($entry->blocks as $block)
			if (($val = blx()->request->getPost($block->handle)) !== null)
				$changes[$block->handle] = $val;

		return $changes;
	}
}
