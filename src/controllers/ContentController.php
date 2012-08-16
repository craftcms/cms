<?php
namespace Blocks;

/**
 * Handles content management tasks
 */
class ContentController extends BaseController
{
	/**
	 * All content actions require the user to be logged in
	 */
	public function init()
	{
		$this->requireLogin();
	}

	/**
	 * Saves a section
	 */
	public function actionSaveSection()
	{
		$this->requirePostRequest();

		$sectionSettings['name']        = blx()->request->getPost('name');
		$sectionSettings['handle']      = blx()->request->getPost('handle');
		$sectionSettings['max_entries'] = blx()->request->getPost('max_entries');
		$sectionSettings['sortable']    = blx()->request->getPost('sortable');
		$sectionSettings['has_urls']    = blx()->request->getPost('has_urls');
		$sectionSettings['url_format']  = blx()->request->getPost('url_format');
		$sectionSettings['template']    = blx()->request->getPost('template');
		$sectionSettings['blocks']      = blx()->request->getPost('blocks');

		$sectionId = blx()->request->getPost('section_id');

		$section = blx()->content->saveSection($sectionSettings, $sectionId);

		// Did it save?
		if (!$section->errors)
		{
			// Did all of the blocks save?
			$blocksSaved = true;
			foreach ($section->blocks as $block)
			{
				if ($block->errors)
				{
					$blocksSaved = false;
					break;
				}
			}

			if ($blocksSaved)
			{
				blx()->user->setNotice('Section saved.');
				$this->redirectToPostedUrl();
			}
			else
				blx()->user->setError('Section saved, but couldn’t save all the content blocks.');
		}
		else
			blx()->user->setError('Couldn’t save section.');


		// Reload the original template
		$this->renderRequestedTemplate(array(
			'section' => $section
		));
	}

	/**
	 * Creates a new entry and returns its edit page
	 */
	public function actionCreateEntry()
	{
		$this->requirePostRequest();

		$sectionId = blx()->request->getRequiredPost('sectionId');
		$title = blx()->request->getPost('title');

		// Create the entry
		$entry = blx()->content->createEntry($sectionId, null, null, $title);

		// Create the first draft
		$draft = blx()->content->createEntryDraft($entry);

		blx()->user->setNotice('Entry created.');
		$this->redirect("content/{$entry->id}/draft{$draft->num}");
	}

	/**
	 * Saves an entry.
	 */
	public function actionSaveEntry()
	{
		$this->requirePostRequest();

		$entry = $this->_getEntry();
		$changes = $this->_getContentFromPost($entry);

		// Save the new entry content
		if (blx()->content->saveEntryContent($entry, $changes))
		{
			blx()->user->setNotice('Entry saved.');
			$this->redirectToPostedUrl();
		}
		else
		{
			blx()->user->setError('Couldn’t save entry.');
		}

		$this->renderRequestedTemplate(array('entry' => $entry));
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

		blx()->user->setNotice('Draft created.');
		$this->redirect("content/{$entry->id}/draft{$draft->num}");
	}

	/**
	 * Saves a draft
	 */
	public function actionSaveDraft()
	{
		$this->requirePostRequest();

		$entry = $this->_getEntry();
		$draft = $this->_getDraft();
		$changes = $this->_getContentFromPost($entry);

		// Save the new draft content
		if (blx()->content->saveDraftContent($draft, $changes))
		{
			blx()->user->setNotice('Draft saved.');
			$this->redirectToPostedUrl();
		}
		else
		{
			blx()->user->setError('Couldn’t save draft.');
		}

		$entry->setDraft($draft);
		$this->renderRequestedTemplate(array('entry' => $entry));
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
			blx()->user->setNotice('Draft published.');
			$this->redirect('content/'.$entry->id);
		}
		else
		{
			blx()->user->setError('Couldn’t publish draft.');
			$entry->setDraft($draft);
			$this->renderRequestedTemplate(array('entry' => $entry));
		}
	}

	/**
	 * Returns an entry based on the entryId in the post data
	 * @access private
	 * @throws Exception
	 * @return Entry
	 */
	private function _getEntry()
	{
		$entryId = blx()->request->getRequiredPost('entryId');
		$entry = blx()->content->getEntryById($entryId);

		if (!$entry)
			throw new Exception(Blocks::t('No entry exists with the Id “{entryId}”.', array('entryId' => $entryId)));

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
			throw new Exception(Blocks::t('No draft exists with the Id “{entryId}”.', array('entryId' => $draft)));

		return $draft;
	}

	/**
	 * Returns any content changes in the post data
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
