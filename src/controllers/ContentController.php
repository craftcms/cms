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
			b()->user->setMessage(MessageType::Notice, 'Section saved.');

			$url = b()->request->getPost('redirect');
			if ($url !== null)
				$this->redirect($url);
		}
		else
		{
			b()->user->setMessage(MessageType::Error, 'Couldn’t save section.');
		}

		// Get Block instances for each selected block
		$sectionBlocks = array();
		if (!empty($sectionBlocksData['selections']))
		{
			foreach ($sectionBlocksData['selections'] as $blockId)
			{
				$block = b()->blocks->getBlockById($blockId);
				$block->required = (isset($sectionBlocksData['required'][$blockId]) && $sectionBlocksData['required'][$blockId] === 'y');
				$sectionBlocks[] = $block;
			}
		}
		$section->blocks = $sectionBlocks;
		

		// Reload the original template
		$this->loadRequestedTemplate(array(
			'section' => $section
		));
	}

	/**
	 * Creates a new entry and redirects to its edit page
	 */
	public function actionCreateEntry()
	{
		$sectionId = b()->request->getParam('sectionId');
		$authorId = b()->user->id;
		$entry = b()->content->createEntry($sectionId, $authorId);
		$this->redirect('content/edit/'.$entry->id);
	}

	/**
	 * Loads an entry
	 */
	public function actionLoadEntry()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$entryId = b()->request->getPost('entryId');
		$draftId = b()->request->getPost('draftId');

		$entry = b()->content->getEntryById($entryId);

		if ($entry)
		{
			$entryHtml = $this->loadTemplate('content/_includes/entry', array('entry' => $entry), true);
			$this->returnJson(array('success' => true, 'entryHtml' => $entryHtml, 'draftId' => 1));
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

		$entryId = b()->request->getPost('entryId');
		$draftId = b()->request->getPost('draftId');
		$changedInputs = b()->request->getPost('changedInputs');

		$blocks = b()->db->createCommand()
			->select('id, handle')
			->from('blocks')
			->where(array('in', 'handle', $changedInputs))
			->queryAll();

		if ($blocks)
		{
			$blockIds = array();
			foreach ($blocks as $block)
			{
				$blockIds[] = $block['id'];
			}

			// Start a transaction
			$transaction = b()->db->beginTransaction();
			try
			{
				$insertVals = array();

				// Check for previous data on this draft
				$draftContent = b()->db->createCommand()
					->select('id, block_id')
					->from('draftcontent')
					->where(array('and', 'draft_id=:draftId', array('in', 'block_id', $blockIds)), array(':draftId' => $draftId))
					->queryAll();
				$draftContentIds = array();
				foreach ($draftContent as $row)
				{
					$draftContentIds[$row['block_id']] = $row['id'];
				}

				// Update existing rows and get ready to insert new ones
				foreach ($blocks as $block)
				{
					$val = b()->request->getPost($block['handle']);
					if (isset($draftContentIds[$block['id']]))
						b()->db->createCommand()->update('draftcontent',
							array('value' => $val),
							'id=:id',
							array(':id' => $draftContentIds[$block['id']]));
					else
						$insertVals[] = array($draftId, $block['id'], $val);
				}

				// Insert new rows
				if ($insertVals)
				{
					$columns = array('draft_id', 'block_id', 'value');
					b()->db->createCommand()->insertAll('draftcontent', $columns, $insertVals);
				}

				$transaction->commit();
			}
			catch (Exception $e)
			{
				$transaction->rollBack();
				$this->returnJson(array('error' => $e->getMessage()));
			}

			$this->returnJson(array('success' => true));
		}
	}

}
