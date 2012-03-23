<?php
namespace Blocks;

/**
 *
 */
class Section extends Model
{
	protected $tableName = 'sections';
	protected $hasBlocks = true;

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
		'parent' => array('model' => 'Section'),
		'site'   => array('model' => 'Site', 'required' => true)
	);

	protected $hasMany = array(
		'children' => array('model' => 'Section', 'foreignKey' => 'parent'),
		'entries'  => array('model' => 'Entry', 'foreignKey' => 'section')
	);

	protected $indexes = array(
		array('columns' => array('site_id', 'handle'), 'unique' => true),
	);

	/**
	 * Content table names are based on the site and section handles
	 */
	public function getContentTableName()
	{
		return 'entrycontent_'.$this->site->handle.'_'.$this->handle;
	}

	/**
	 * Section content tables reference entries, not sections
	 */
	public function createContentTable()
	{
		b()->db->createCommand()->createContentTable($this->getContentTableName(), 'entries', 'entry_id');
	}

	/**
	 * Create a corresponding content table each time a new section is created
	 */
	public function save($runValidation = true, $attributes = null)
	{
		$isNewRecord = $this->isNewRecord;
		$return = parent::save($runValidation, $attributes);

		// Create the content if the save was successful
		if ($isNewRecord && $return)
			$this->createContentTable();

		return $return;
	}

}
