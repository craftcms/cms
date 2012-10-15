<?php
namespace Blocks;

/**
 *
 */
class PagesService extends BaseApplicationComponent
{
	/**
	 * Populates a page model.
	 *
	 * @param array|PageRecord $attributes
	 * @return PageModel
	 */
	public function populatePage($attributes)
	{
		$page = PageModel::populateModel($attributes);

		// Set the block content
		$content = $this->getPageContentRecordByPageId($page->id);

		if ($content)
		{
			$blocks = blx()->pageBlocks->getBlocksByPageId($page->id);

			$page->setBlockValuesFromAttributes($blocks, $content->content, 'id');
		}

		return $page;
	}

	/**
	 * Mass-populates page models.
	 *
	 * @param array  $data
	 * @param string $index
	 * @return array
	 */
	public function populatePages($data, $index = 'id')
	{
		$pages = array();

		foreach ($data as $attributes)
		{
			$page = $this->populatePage($attributes);
			$pages[$page->$index] = $page;
		}

		return $pages;
	}

	/**
	 * Gets all pages.
	 *
	 * @return array
	 */
	public function getAllPages()
	{
		$pageRecords = PageRecord::model()->ordered()->findAll();
		return $this->populatePages($pageRecords);
	}

	/**
	 * Gets the total number of pages.
	 *
	 * @return int
	 */
	public function getTotalPages(PageParams $params = null)
	{
		return PageRecord::model()->count();
	}

	/**
	 * Gets a page by its ID.
	 *
	 * @param $pageId
	 * @return PageModel|null
	 */
	public function getPageById($pageId)
	{
		$pageRecord = PageRecord::model()->findById($pageId);
		if ($pageRecord)
		{
			return $this->populatePage($pageRecord);
		}
	}

	/**
	 * Gets a page by its URI.
	 *
	 * @param string $uri
	 * @return PageModel|null
	 */
	public function getPageByUri($uri)
	{
		$pageRecord = PageRecord::model()->findByAttributes(array(
			'uri' => $uri
		));

		if ($pageRecord)
		{
			return $this->populatePage($pageRecord);
		}
	}

	/**
	 * Saves a page.
	 *
	 * @param PageModel $page
	 * @throws \Exception
	 * @return bool
	 */
	public function savePage(PageModel $page)
	{
		$pageRecord = $this->_getPageRecordById($page->id);

		$pageRecord->title     = $page->title;
		$pageRecord->uri       = $page->uri;
		$pageRecord->template  = $page->template;

		if ($pageRecord->save())
		{
			// Now that we have a page ID, save it on the model
			if (!$page->id)
			{
				$page->id = $pageRecord->id;
			}

			return true;
		}
		else
		{
			$page->addErrors($pageRecord->getErrors());
			return false;
		}
	}

	/**
	 * Saves a page's content
	 *
	 * @param PageModel $page
	 * @return bool
	 */
	public function savePageContent(PageModel $page)
	{
		$contentRecord = $this->getPageContentRecordByPageId($page->id);

		$blocks = blx()->pageBlocks->getBlocksByPageId($page->id);

		$content = array();
		$blockTypes = array();

		foreach ($blocks as $block)
		{
			$blockType = blx()->blockTypes->populateBlockType($block);
			$blockType->entity = $page;

			if ($blockType->defineContentAttribute() !== false)
			{
				$handle = $block->handle;
				$content[$block->id] = $blockType->getInputValue();
			}

			// Keep the block type instance around for calling onAfterEntitySave()
			$blockTypes[] = $blockType;
		}

		$contentRecord->content = $content;

		if ($contentRecord->save())
		{
			// Give the block types a chance to do any post-processing
			foreach ($blockTypes as $blockType)
			{
				$blockType->onAfterEntitySave();
			}

			return true;
		}
		else
		{
			$page->addErrors($contentRecord->getErrors());
		}
	}

	/**
	 * Deletes a page by its ID.
	 *
	 * @param int $pageId
	 * @throws \Exception
	 * @return bool
	*/
	public function deletePageById($pageId)
	{
		$pageRecord = $this->_getPageRecordById($pageId);

		$transaction = blx()->db->beginTransaction();
		try
		{
			// Delete the page blocks
			blx()->db->createCommand()
				->delete('pageblocks', array('pageId' => $pageId));

			// Delete the page content
			blx()->db->createCommand()
				->delete('pagecontent', array('pageId' => $pageId));

			// Delete the page
			$pageRecord->delete();

			$transaction->commit();
		}
		catch (\Exception $e)
		{
			$transaction->rollBack();
			throw $e;
		}

		return true;
	}

	/**
	 * Gets a page record or creates a new one.
	 *
	 * @access private
	 * @param int $pageId
	 * @throws Exception
	 * @return PageRecord
	 */
	private function _getPageRecordById($pageId = null)
	{
		if ($pageId)
		{
			$pageRecord = PageRecord::model()->findById($pageId);

			if (!$pageRecord)
			{
				throw new Exception(Blocks::t('No page exists with the ID “{id}”', array('id' => $pageId)));
			}
		}
		else
		{
			$pageRecord = new PageRecord();
		}

		return $pageRecord;
	}

	/**
	 * Gets a page content record by the page ID, or creates a new one.
	 *
	 * @param int $pageId
	 * @return PageContentRecord
	 */
	public function getPageContentRecordByPageId($pageId)
	{
		$record = PageContentRecord::model()->findByAttributes(array(
			'pageId'   => $pageId,
			'language' => blx()->language,
		));

		if (empty($record))
		{
			$record = new PageContentRecord();
			$record->pageId = $pageId;
			$record->language = blx()->language;
		}

		return $record;
	}
}
