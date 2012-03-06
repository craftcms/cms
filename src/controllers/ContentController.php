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

		$sectionSettings['name'] = Blocks::app()->request->getPost('name');
		$sectionSettings['handle'] = Blocks::app()->request->getPost('handle');

		$maxEntries = Blocks::app()->request->getPost('max_entries');
		$sectionSettings['max_entries'] = ($maxEntries ? $maxEntries : null);

		$sectionSettings['sortable'] = (Blocks::app()->request->getPost('sortable') === 'y');

		$urlFormat = Blocks::app()->request->getPost('url_format');
		$sectionSettings['url_format'] = ($urlFormat ? $urlFormat : null);

		$template = Blocks::app()->request->getPost('template');
		$sectionSettings['template'] = ($template ? $template : null);

		$sectionBlocksData = Blocks::app()->request->getPost('blocks');
		$sectionId = Blocks::app()->request->getPost('section_id');

		$section = Blocks::app()->content->saveSection($sectionSettings, $sectionBlocksData, $sectionId);

		// Did it save?
		if (!$section->errors)
		{
			Blocks::app()->user->setMessage(MessageStatus::Success, 'Section saved successfully.');

			$url = Blocks::app()->request->getPost('redirect');
			if ($url !== null)
				$this->redirect($url);
		}

		// Get Block instances for each selected block
		$sectionBlocks = array();

		if (!empty($sectionBlocksData['selections']))
		{
			foreach ($sectionBlocksData['selections'] as $blockId)
			{
				$block = Blocks::app()->blocks->getBlockById($blockId);
				$block->required = (isset($sectionBlocksData['required'][$blockId]) && $sectionBlocksData['required'][$blockId] === 'y');
				$sectionBlocks[] = $block;
			}
		}
		

		// Reload the original template
		$this->loadRequestedTemplate(array(
			'section'       => $section,
			'sectionBlocks' => $sectionBlocks
		));
	}

	/**
	 * Creates a new entry and redirects to its edit page
	 */
	public function actionCreateEntry()
	{
		$sectionId = Blocks::app()->request->getParam('sectionId');
		$authorId = Blocks::app()->user->id;
		$entry = Blocks::app()->content->createEntry($sectionId, $authorId);
		$this->redirect('content/edit/'.$entry->id);
	}
}
