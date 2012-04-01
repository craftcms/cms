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

				$url = b()->request->getPost('redirect');
				if ($url !== null)
					$this->redirect($url);
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
		$this->requireAjaxRequest();

		try
		{
			$sectionId = b()->request->getRequiredPost('sectionId');
			$title     = b()->request->getPost('title');

			// Create the entry
			$entry = b()->content->createEntry($sectionId, null, null, $title);

			// Save its slug
			if ($entry->section->has_urls)
				b()->content->saveEntrySlug($entry, strtolower($title));

			// Create the first draft
			$entry->draft = b()->content->createDraft($entry->id, null, 'Draft 1');

			$this->returnEntryJson($entry);
		}
		catch (\Exception $e)
		{
			$this->returnJsonError($e->getMessage());
		}
	}

	/**
	 * Loads an entry
	 */
	public function actionLoadEntryEditPage()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		try
		{
			$entryId = b()->request->getRequiredPost('entryId');

			$entry = b()->content->getEntryById($entryId);
			if (!$entry)
				$this->returnJsonError('No entry exists with the ID '.$entryId);

			// Is there a requested draft?
			$draftNum = b()->request->getPost('draftNum');
			if ($draftNum)
				$draft = b()->content->getDraftByNum($entryId, $draftNum);

			// We must fetch a draft if the entry hasn't been published
			if (empty($draft) && !$entry->published)
			{
				$draft = b()->content->getLatestDraft($entry->id);
				if (!$draft)
					$draft = b()->content->createDraft($entry->id);
			}

			if (!empty($draft))
				$entry->draft = $draft;

			$this->returnEntryEditPage($entry);
		}
		catch (\Exception $e)
		{
			$this->returnJsonError($e->getMessage());
		}
	}

	/**
	 * Creates a new draft
	 */
	public function actionCreateDraft()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		try
		{
			$entryId = b()->request->getRequiredPost('entryId');

			$entry = b()->content->getEntryById($entryId);
			if (!$entry)
				$this->returnJsonError('No entry exists with the ID '.$entryId);

			$draftName = b()->request->getPost('draftName');
			$draft = b()->content->createDraft($entryId, null, $draftName);
			$entry->draft = $draft;

			$this->returnEntryEditPage($entry);
		}
		catch (\Exception $e)
		{
			$this->returnJsonError($e->getMessage());
		}
	}

	/**
	 * Autosaves a draft
	 */
	public function actionAutosaveDraft()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		try
		{
			$entryId = b()->request->getRequiredPost('entryId');
			$entry = b()->content->getEntryById($entryId);
			if (!$entryId)
				$this->returnJsonError('No entry exists with the ID '.$entryId);

			$content = b()->request->getRequiredPost('content');

			// Get the draft, or create a new one
			$draftId = b()->request->getPost('draftId');
			if ($draftId)
			{
				$draft = b()->content->getDraftById($draftId);
				if (!$draft)
					$this->returnJsonError('No draft exists with the ID '.$draftId);
			}
			else
				$draft = b()->content->createDraft($entryId);

			// Save the new draft content
			b()->content->saveDraftChanges($draft, $content);

			$entry->draft = $draft;
			$this->returnEntryJson($entry);
		}
		catch (\Exception $e)
		{
			$this->returnJsonError($e->getMessage());
		}
	}

	/**
	 * Publishes a draft
	 */
	public function actionPublishDraft()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		try
		{
			$entryId = b()->request->getRequiredPost('entryId');
			$entry = b()->content->getEntryById($entryId);
			if (!$entryId)
				$this->returnJsonError('No entry exists with the ID '.$entryId);

			$draftId = b()->request->getPost('draftId');
			if ($draftId)
			{
				$draft = b()->content->getDraftById($draftId);
				if (!$draft)
					$this->returnJsonError('No draft exists with the ID '.$draftId);
			}
			else
				$draft = b()->content->createDraft($entryId);

			// Save any last-minute content changes
			$content = b()->request->getPost('content');
			if ($content)
				b()->content->saveDraftChanges($draft, $content);

			// Publish it
			b()->content->publishDraft($draft->id);

			$this->returnEntryJson($entry);
		}
		catch (\Exception $e)
		{
			$this->returnJsonError($e->getMessage());
		}
	}

	/**
	 * Returns entry data used by Entry.js.
	 * @access private
	 * @param Entry $entry
	 * @param array $return Any additional values to return.
	 */
	private function returnEntryJson($entry, $return = array())
	{
		$return['entryId']     = $entry->id;
		$return['entryTitle']  = $entry->title;
		$return['entryStatus'] = $entry->status;

		if ($entry->draft)
		{
			$return['draftId']     = $entry->draft->id;
			$return['draftNum']    = $entry->draft->num;
			$return['draftName']   = $entry->draft->name;
			$return['draftAuthor'] = $entry->draft->author->firstNameLastInitial;
		}
		else
		{
			$return['draftId']     = null;
			$return['draftNum']    = null;
			$return['draftName']   = null;
			$return['draftAuthor'] = null;
		}

		$return['success'] = true;
		$this->returnJson($return);
	}

	/**
	 * Returns an entry edit page.
	 * @access private
	 * @param Entry $entry
	 */
	private function returnEntryEditPage($entry)
	{
		$return['entryHtml']  = $this->loadTemplate('content/_includes/entry', array('entry' => $entry), true);
		$this->returnEntryJson($entry, $return);
	}
}
