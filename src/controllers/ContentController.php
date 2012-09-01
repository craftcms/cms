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

		$sectionId = blx()->request->getPost('sectionId');

		$settings['name']       = blx()->request->getPost('name');
		$settings['handle']     = blx()->request->getPost('handle');
		$settings['has_urls']   = blx()->request->getPost('has_urls');
		$settings['url_format'] = blx()->request->getPost('url_format');
		$settings['template']   = blx()->request->getPost('template');

		$section = blx()->content->saveSection($settings, $sectionId);

		// Did it save?
		if (!$section->getErrors())
		{
			blx()->user->setNotice(Blocks::t('Section saved.'));
			$this->redirectToPostedUrl();
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
	 * Saves a section block.
	 */
	public function actionSaveSectionBlock()
	{
		$this->requirePostRequest();

		$sectionId = blx()->request->getRequiredPost('sectionId');
		$blockId   = blx()->request->getPost('blockId');
		$class     = blx()->request->getRequiredPost('class');

		$settings['name']         = blx()->request->getPost('name');
		$settings['handle']       = blx()->request->getPost('handle');
		$settings['instructions'] = blx()->request->getPost('instructions');
		$settings['required']     = blx()->request->getPost('required');
		$settings['translatable'] = blx()->request->getPost('translatable');

		$blocktypeSettings = blx()->request->getPost('settings');
		$settings['class']    = $class;
		$settings['settings'] = isset($blocktypeSettings[$class]) ? $blocktypeSettings[$class] : null;

		$block = blx()->content->saveSectionBlock($sectionId, $settings, $blockId);

		// Did it save?
		if (!$block->getErrors())
		{
			blx()->user->setNotice(Blocks::t('Content block saved.'));
			$this->redirectToPostedUrl();
		}
		else
		{
			blx()->user->setError(Blocks::t('Couldn’t save content block.'));
		}

		// Reload the original template
		$this->renderRequestedTemplate(array(
			'block' => $block
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

		blx()->user->setNotice(Blocks::t('Entry created.'));
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
			blx()->user->setNotice(Blocks::t('Entry saved.'));
			$this->redirectToPostedUrl();
		}
		else
		{
			blx()->user->setError(Blocks::t('Couldn’t save entry.'));
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

		blx()->user->setNotice(Blocks::t('Draft created.'));
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
			blx()->user->setNotice(Blocks::t('Draft saved.'));
			$this->redirectToPostedUrl();
		}
		else
		{
			blx()->user->setError(Blocks::t('Couldn’t save draft.'));
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
			blx()->user->setNotice(Blocks::t('Draft published.'));
			$this->redirect('content/'.$entry->id);
		}
		else
		{
			blx()->user->setError(Blocks::t('Couldn’t publish draft.'));
			$entry->setDraft($draft);
			$this->renderRequestedTemplate(array('entry' => $entry));
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
			throw new Exception(Blocks::t('No entry exists with the ID “{entryId}”.', array('entryId' => $entryId)));

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
			throw new Exception(Blocks::t('No draft exists with the ID “{entryId}”.', array('entryId' => $draft)));

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
