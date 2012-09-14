<?php
namespace Blocks;

/**
 *
 */
class EntryContentRecord extends BaseRecord
{
	protected $section;

	/**
	 * Constructor
	 *
	 * @param SectionPackage $section
	 */
	public function __construct(SectionPackage $section = null)
	{
		if ($section && $section instanceof SectionPackage)
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
	 * @param SectionPackage $section
	 * @return string
	 */
	public static function getTableNameForSection(SectionPackage $section)
	{
		return 'entrycontent_'.$section->handle;
	}

	public function defineAttributes()
	{
		$attributes = array(
			'language' => array(AttributeType::Language, 'required' => true),
		);

		$blockPackages = blx()->content->getEntryBlocksBySectionId($this->section->id);
		foreach ($blockPackages as $blockPackage)
		{
			$block = blx()->blocks->getBlockByClass($blockPackage->class);
			$block->getSettings()->setAttributes($blockPackage->settings);
			$attributes[$blockPackage->handle] = $block->defineContentAttribute();
		}

		return $attributes;
	}

	public function defineRelations()
	{
		return array(
			'entry' => array(static::BELONGS_TO, 'EntryRecord', 'required' => true),
		);
	}

	public function defineIndexes()
	{
		return array(
			array('columns' => array('language', 'entryId'), 'unique' => true),
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
