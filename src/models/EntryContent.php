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
			return static::getTableNameForSection($this->section);
		else
			throw new Exception(Blocks::t('Cannot get the table name if a section hasn’t been defined.'));
	}

	/**
	 * Returns the table name for an entry content table.
	 * (lame that this can't also be called getTableName() -- see https://bugs.php.net/bug.php?id=40837)
	 *
	 * @static
	 * @param Section $section
	 * @return string
	 */
	public static function getTableNameForSection($section)
	{
		return 'entrycontent_'.$section->handle;
	}

	protected function getProperties()
	{
		return array(
			'language' => PropertyType::Language,
			'title'    => PropertyType::Varchar,
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
			array('columns' => array('entryId', 'language'), 'unique' => true),
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
