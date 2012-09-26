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

		$section = new SectionPackage();
		$section->id = blx()->request->getPost('sectionId');
		$section->name = blx()->request->getPost('name');
		$section->handle = blx()->request->getPost('handle');
		$section->hasUrls = blx()->request->getPost('hasUrls');
		$section->urlFormat = blx()->request->getPost('urlFormat');
		$section->template = blx()->request->getPost('template');

		if ($section->save())
		{
			blx()->user->setNotice(Blocks::t('Section saved.'));
			$this->redirectToPostedUrl(array(
				'sectionId' => $section->id
			));
		}
		else
		{
			blx()->user->setError(Blocks::t('Couldn’t save section.'));
		}

		// Reload the original template
		$this->renderRequestedTemplate(array(
			'section' => $section
		));
	}

	/**
	 * Deletes a section.
	 */
	public function actionDeleteSection()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$sectionId = blx()->request->getRequiredPost('id');

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

		$entry = new EntryPackage();
		$this->_populateEntryFromPost($entry);

		if ($entry->save())
		{
			blx()->user->setNotice(Blocks::t('Entry saved.'));

			// Do we need to delete a draft?
			if (($draftId = blx()->request->getPost('draftId')) !== null)
			{
				blx()->content->deleteEntryDraftById($draftId);
			}

			$this->redirectToPostedUrl(array(
				'entryId' => $entry->id
			));
		}
		else
		{
			blx()->user->setError(Blocks::t('Couldn’t save entry.'));
		}

		$this->renderRequestedTemplate(array(
			'entry' => $entry
		));
	}

	/**
	 * Saves an entry draft.
	 */
	public function actionSaveEntryDraft()
	{
		$draft = new EntryDraftPackage();
		$this->_populateEntryFromPost($draft);
		$draft->draftId = blx()->request->getPost('draftId');

		if ($draft->save())
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
		}

		$this->renderRequestedTemplate(array(
			'entry' => $draft
		));
	}

	/**
	 * Populates an entry package from post data.
	 *
	 * @param EntryPackage $entry
	 */
	private function _populateEntryFromPost(EntryPackage $entry)
	{
		$entry->id = blx()->request->getPost('entryId');
		/* BLOCKSPRO ONLY */
		$entry->authorId = blx()->accounts->getCurrentUser()->id;
		$entry->sectionId = blx()->request->getRequiredPost('sectionId');
		$entry->language = blx()->request->getPost('language');
		/* end BLOCKSPRO ONLY */
		$entry->title = blx()->request->getPost('title');
		$entry->slug = blx()->request->getPost('slug');

		if ($postDate = blx()->request->getPost('postDate'))
		{
			$entry->postDate = DateTime::createFromFormat(DateTime::W3C_DATE, $postDate);
		}

		/* BLOCKSPRO ONLY */
		if ($expiryDate = blx()->request->getPost('expiryDate'))
		{
			$entry->expiryDate = DateTime::createFromFormat(DateTime::W3C_DATE, $expiryDate);
		}

		/* end BLOCKSPRO ONLY */
		$entry->blocks = blx()->request->getPost('blocks');
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
