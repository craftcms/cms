<?php
namespace Blocks;

/**
 *
 */
class Section extends BaseModel
{
	protected $tableName = 'sections';
	public $hasBlocks = true;

	protected $attributes = array(
		'name'        => AttributeType::Name,
		'handle'      => AttributeType::Handle,
		'max_entries' => array('type' => AttributeType::TinyInt, 'unsigned' => true),
		'has_urls'    => array('type' => AttributeType::Boolean, 'default' => true),
		'url_format'  => AttributeType::Varchar,
		'template'    => AttributeType::Template,
		'sortable'    => AttributeType::Boolean
	);

	protected $belongsTo = array(
		'parent' => array('model' => 'Section')
	);

	protected $hasMany = array(
		'children' => array('model' => 'Section', 'foreignKey' => 'parent'),
		'entries'  => array('model' => 'Entry', 'foreignKey' => 'section')
	);

	protected $indexes = array(
		array('columns' => array('handle'), 'unique' => true),
	);

	/**
	 * Content table names are based on the site and section handles
	 * @return string
	 */
	public function getContentTableName()
	{
		return 'entrycontent_'.$this->handle;
	}

	/**
	 * Section content tables reference entries, not sections
	 */
	public function createContentTable()
	{
		$table = $this->getContentTableName();

		// Create the content table
		blx()->db->createCommand()->createContentTable($table, 'entries', 'entry_id');

		// Add the title column and index it
		blx()->db->createCommand()->addColumn($table, 'title', array('type' => AttributeType::Varchar, 'required' => true));
		blx()->db->createCommand()->createIndex("{$table}_title_idx", $table, 'title');
	}
}
