<?php
namespace Blocks;

/**
 *
 */
class EntryContent extends BaseModel
{
	protected $section;

	/**
	 * Constructor
	 *
	 * @param Section $section
	 */
	public function __construct($section = null)
	{
		if ($section && $section instanceof Section)
			$this->section = $section;

		parent::__construct(null);
	}

	public function getTableName()
	{
		if (isset($this->section))
			return 'entrycontent_'.$this->section->handle;
		else
			throw new Exception(Blocks::t('Cannot get the table name if a section hasn’t been defined.'));
	}

	protected function getProperties()
	{
		return array(
			/* BLOCKSPRO ONLY */
			'language' => PropertyType::Language,
			/* end BLOCKSPRO ONLY */
		);
	}

	protected function getRelations()
	{
		return array(
			'entry' => array(static::BELONGS_TO, 'Entry', 'required' => true),
		);
	}

	protected function getIndexes()
	{
		return array(
			array('columns' => array('entry_id', 'language'), 'unique' => true),
		);
	}

	/* Prevent table creation/deletion unless $section has been set */

	public function createTable()
	{
		if (isset($this->section))
			parent::createTable();
	}

	public function dropTable()
	{
		if (isset($this->section))
			parent::dropTable();
	}

	public function addForeignKeys()
	{
		if (isset($this->section))
			parent::addForeignKeys();
	}

	public function dropForeignKeys()
	{
		if (isset($this->section))
			parent::dropForeignKeys();
	}
}
