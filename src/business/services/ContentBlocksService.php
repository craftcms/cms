<?php
namespace Blocks;

/**
 *
 */
class ContentBlocksService extends BaseService
{

	/**
	 * Returns all content blocks
	 * @return array
	 */
	public function getAll()
	{
		return ContentBlock::model()->findAll();
	}

	/**
	 * Returns a block by its ID
	 * @return ContentBlock
	 */
	public function getBlockById($blockId)
	{
		return ContentBlock::model()->findByPk($blockId);
	}

	/**
	 * Returns all block types
	 * @return array
	 */
	public function getBlockTypes()
	{
		return array(
			array('class' => 'Assets',       'name' => 'Assets'),
			array('class' => 'Checkboxes',   'name' => 'Checkboxes'),
			array('class' => 'Dropdown',     'name' => 'Dropdown'),
			array('class' => 'List',         'name' => 'List'),
			array('class' => 'Multiselect',  'name' => 'Multiselect'),
			array('class' => 'PillSelect',   'name' => 'Pill Select'),
			array('class' => 'RadioButtons', 'name' => 'Radio Buttons'),
			array('class' => 'Switch',       'name' => 'Switch'),
			array('class' => 'Table',        'name' => 'Table'),
			array('class' => 'Text',         'name' => 'Text'),
		);
	}

}
