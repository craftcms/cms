<?php
namespace Blocks;

/**
 * Handles content management tasks
 */
class ContentController extends BaseController
{
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

		$sectionBlockIds = Blocks::app()->request->getPost('blocks', array());
		$sectionId = Blocks::app()->request->getPost('section_id');

		$section = Blocks::app()->content->saveSection($sectionSettings, $sectionBlockIds, $sectionId);

		// Did it save?
		if (!$section->errors)
		{
			Blocks::app()->user->setMessage(MessageStatus::Success, 'Section saved successfully.');

			$url = Blocks::app()->request->getPost('redirect');
			if ($url !== null)
				$this->redirect($url);
		}

		// Get ContentBlock instances for each selected block
		$sectionBlocks = array();
		foreach ($sectionBlockIds as $blockId)
		{
			$block = Blocks::app()->contentBlocks->getBlockById($blockId);
			if ($block)
				$sectionBlocks[] = $block;
		}

		// Reload the original template
		$this->loadRequestedTemplate(array(
			'section'       => $section,
			'sectionBlocks' => $sectionBlocks
		));
	}
}
