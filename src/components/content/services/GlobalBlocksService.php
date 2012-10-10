<?php
namespace Blocks;

/**
 *
 */
class GlobalBlocksService extends BaseBlocksService
{
	/**
	 * The block model class name.
	 *
	 * @access protected
	 * @var string
	 */
	protected $blockModelClass = 'GlobalBlockModel';

	/**
	 * The block record class name.
	 *
	 * @access protected
	 * @var string
	 */
	protected $blockRecordClass = 'GlobalBlockRecord';

	/**
	 * The content record class name.
	 *
	 * @access protected
	 * @var string
	 */
	protected $contentRecordClass = 'GlobalContentRecord';

	/**
	 * Gets the global content.
	 *
	 * @return GlobalContentModel
	 */
	public function getGlobalContent()
	{
		$content = new GlobalContentModel();

		$blocks = $this->getAllBlocks();
		$record = $this->_getGlobalContentRecord();
		$content->setBlockValuesFromAttributes($blocks, $record);

		return $content;
	}

	/**
	 * Saves the global content.
	 *
	 * @param GlobalContentModel $content
	 */
	public function saveGlobalContent(GlobalContentModel $content)
	{
		$record = $this->_getGlobalContentRecord();

		$blockTypes = array();

		foreach ($this->getAllBlocks() as $block)
		{
			$blockType = blx()->blockTypes->populateBlockType($block);
			$blockType->entity = $content;

			if ($blockType->defineContentAttribute() !== false)
			{
				$handle = $block->handle;
				$record->$handle = $blockType->getInputValue();
			}

			// Keep the block type instance around for calling onAfterEntitySave()
			$blockTypes[] = $blockType;
		}

		if ($record->save())
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
			$content->addErrors($record->getErrors());
			return false;
		}
	}

	/**
	 * Gets the global content record or creates a new one.
	 *
	 * @access private
	 * @return GlobalContentRecord
	 */
	private function _getGlobalContentRecord()
	{
		if (Blocks::hasPackage(BlocksPackage::Language))
		{
			$record = GlobalContentRecord::model()->findByAttributes(array(
				'language' => blx()->language
			));
		}
		else
		{
			$record = GlobalContentRecord::model()->find();
		}

		if (!$record)
		{
			$record = new GlobalContentRecord();

			if (Blocks::hasPackage(BlocksPackage::Language))
			{
				$record->language = blx()->language;
			}
		}

		return $record;
	}
}
