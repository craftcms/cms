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

		$sectionSettings['name'] = b()->request->getPost('name');
		$sectionSettings['handle'] = b()->request->getPost('handle');

		$maxEntries = b()->request->getPost('max_entries');
		$sectionSettings['max_entries'] = ($maxEntries ? $maxEntries : null);

		$sectionSettings['sortable'] = (b()->request->getPost('sortable') === 'y');
		$sectionSettings['has_urls'] = (b()->request->getPost('has_urls') === 'y');

		$urlFormat = b()->request->getPost('url_format');
		$sectionSettings['url_format'] = ($urlFormat ? $urlFormat : null);

		$template = b()->request->getPost('template');
		$sectionSettings['template'] = ($template ? $template : null);

		$sectionBlocksData = b()->request->getPost('blocks');
		$sectionId = b()->request->getPost('section_id');

		$section = b()->content->saveSection($sectionSettings, $sectionBlocksData, $sectionId);

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

		$sectionId = b()->request->getPost('sectionId');
		$title = b()->request->getPost('title');

		// Create the entry
		$entry = b()->content->createEntry($sectionId, null, null, $title);

		// Save its slug
		if ($entry->section->has_urls)
			b()->content->saveEntrySlug($entry, strtolower($title));

		// Create the first draft
		$draft = b()->content->createDraft($entry->id, null, 'Draft 1');

		$this->returnJson(array(
			'success'    => true,
			'entryId'    => $entry->id,
			'entryTitle' => $entry->title,
			'draftId'    => $draft->id,
			'draftNum'   => $draft->num
		));
	}

	/**
	 * Loads an entry
	 */
	public function actionLoadEntryEditPage()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		// Try and find the entry
		$entryId = b()->request->getPost('entryId');
		if ($entryId)
		{
			$entry = b()->content->getEntryById($entryId);
			if ($entry)
			{
				$return['success']    = true;
				$return['entryId']    = $entry->id;
				$return['entryTitle'] = $entry->title;

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

				// Mix in any draft changes
				if (!empty($draft))
				{
					$entry->mixInDraftContent($draft);	

					$return['draftId']   = $draft->id;
					$return['draftNum']  = $draft->num;
					$return['draftName'] = $draft->name;
				}

				$return['entryHtml']  = $this->loadTemplate('content/_includes/entry', array('entry' => $entry), true);

				$this->returnJson($return);
			}
		}
		else
			throw new HttpException(404);
	}

	/**
	 * Autosaves a draft
	 */
	public function actionAutosaveDraft()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$draftId = b()->request->getPost('draftId');
		$content = b()->request->getPost('content');

		try
		{
			// Save the new draft content
			b()->content->saveDraftContent($draftId, $content);

			$this->returnJson(array('success' => true));
		}
		catch (\Exception $e)
		{
			$this->returnJson(array('error' => $e->getMessage()));
		}
	}

	/**
	 * Publishes a draft
	 */
	public function actionPublishDraft()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$draftId = b()->request->getPost('draftId');
		$content = b()->request->getPost('content');

		try
		{
			// Any last-minute content changes?
			if ($content)
				b()->content->saveDraftContent($draftId, $content);

			// Publish it
			b()->content->publishDraft($draftId);

			$this->returnJson(array('success' => true));
		}
		catch (\Exception $e)
		{
			$this->returnJson(array('error' => $e->getMessage()));
		}

	}
}
