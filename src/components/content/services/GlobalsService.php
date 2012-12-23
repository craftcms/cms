<?php
namespace Blocks;

/**
 *
 */
class GlobalsService extends BaseEntityService
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
		return new GlobalContentModel();
	}

	/**
	 * Saves the global content.
	 *
	 * @param GlobalContentModel $content
	 */
	public function saveGlobalContent(GlobalContentModel $content)
	{
		$record = $this->getGlobalContentRecord();

		$blockTypes = array();

		foreach ($this->getAllBlocks() as $block)
		{
			$blockType = blx()->blockTypes->populateBlockType($block);
			$blockType->entity = $content;

			if ($blockType->defineContentAttribute() !== false)
			{
				$handle = $block->handle;
				$record->$handle = $blockType->getPostData();
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
	 * @return GlobalContentRecord
	 */
	public function getGlobalContentRecord()
	{
		$record = GlobalContentRecord::model()->findByAttributes(array(
			'language' => Blocks::getLanguage()
		));

		if (!$record)
		{
			$record = new GlobalContentRecord();
			$record->language = Blocks::getLanguage();
		}

		return $record;
	}
}
