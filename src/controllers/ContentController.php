<?php
namespace Blocks;

/**
 * Handles content management tasks
 */
class ContentController extends Controller
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

		$sectionSettings['name']        = b()->request->getPost('name');
		$sectionSettings['handle']      = b()->request->getPost('handle');
		$sectionSettings['max_entries'] = b()->request->getPost('max_entries');
		$sectionSettings['sortable']    = b()->request->getPost('sortable');
		$sectionSettings['has_urls']    = b()->request->getPost('has_urls');
		$sectionSettings['url_format']  = b()->request->getPost('url_format');
		$sectionSettings['template']    = b()->request->getPost('template');
		$sectionSettings['blocks']      = b()->request->getPost('blocks');

		$sectionId = b()->request->getPost('section_id');

		$section = b()->content->saveSection($sectionSettings, $sectionId);

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
				b()->user->setMessage(MessageType::Notice, 'Section saved.');
				$this->redirectToPostedUrl();
			}
			else
				b()->user->setMessage(MessageType::Error, 'Section saved, but couldn’t save all the content blocks.');
		}
		else
			b()->user->setMessage(MessageType::Error, 'Couldn’t save section.');


		// Reload the original template
		$this->loadRequestedTemplate(array(
			'section' => $section
		));
	}

	/**
	 * Creates a new entry and returns its edit page
	 */
	public function actionCreateEntry()
	{
		$this->requirePostRequest();

		$sectionId = b()->request->getRequiredPost('sectionId');
		$title = b()->request->getPost('title');

		// Create the entry
		$entry = b()->content->createEntry($sectionId, null, null, $title);

		// Create the first draft
		$draft = b()->content->createEntryDraft($entry);

		b()->user->setMessage(MessageType::Notice, 'Entry created.');
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
		if (b()->content->saveEntryContent($entry, $changes))
		{
			b()->user->setMessage(MessageType::Notice, 'Entry saved.');
			$this->redirectToPostedUrl();
		}
		else
		{
			b()->user->setMessage(MessageType::Error, 'Couldn’t save entry.');
		}

		$this->loadRequestedTemplate(array('entry' => $entry));
	}

	/**
	 * Creates a new draft.
	 */
	public function actionCreateDraft()
	{
		$this->requirePostRequest();

		$entry = $this->_getEntry();
		$changes = $this->_getContentFromPost($entry);
		$draftName = b()->request->getPost('draftName');

		// Create the new draft
		$draft = b()->content->createEntryDraft($entry, $changes, $draftName);

		b()->user->setMessage(MessageType::Notice, 'Draft created.');
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
		if (b()->content->saveDraftContent($draft, $changes))
		{
			b()->user->setMessage(MessageType::Notice, 'Draft saved.');
			$this->redirectToPostedUrl();
		}
		else
		{
			b()->user->setMessage(MessageType::Error, 'Couldn’t save draft.');
		}

		$entry->setDraft($draft);
		$this->loadRequestedTemplate(array('entry' => $entry));
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
			b()->content->saveDraftContent($draft, $changes);

		// Publish the draft
		$draft->entry = $entry;
		if (b()->content->publishEntryDraft($draft))
		{
			b()->user->setMessage(MessageType::Notice, 'Draft published.');
			$this->redirect('content/'.$entry->id);
		}
		else
		{
			b()->user->setMessage(MessageType::Error, 'Couldn’t publish draft.');
			$entry->setDraft($draft);
			$this->loadRequestedTemplate(array('entry' => $entry));
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
		$entryId = b()->request->getRequiredPost('entryId');
		$entry = b()->content->getEntryById($entryId);

		if (!$entry)
			throw new Exception('No entry exists with the ID '.$entryId);

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
		$draftId = b()->request->getRequiredPost('draftId');
		$draft = b()->content->getDraftById($draftId);

		if (!$draft)
			throw new Exception('No draft exists with the ID '.$draftId);

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

		if (($title = b()->request->getPost('title')) !== null)
			$changes['title'] = $title;

		foreach ($entry->blocks as $block)
			if (($val = b()->request->getPost($block->handle)) !== null)
				$changes[$block->handle] = $val;

		return $changes;
	}
}
