<?php
namespace Blocks;

/**
 *
 */
class Section extends BaseModel
{
	protected $tableName = 'sections';

	protected $attributes = array(
		'name'        => AttributeType::Name,
		'handle'      => AttributeType::Handle,
		'url_format'  => AttributeType::Varchar,
		'max_entries' => array('type' => AttributeType::TinyInt, 'unsigned' => true),
		'template'    => AttributeType::Template,
		'sortable'    => AttributeType::Boolean
	);

	protected $belongsTo = array(
		'parent' => array('model' => 'Section'),
		'site'   => array('model' => 'Site', 'required' => true)
	);

	protected $hasBlocks = array(
		'blocks' => array('through' => 'SectionBlock', 'foreignKey' => 'section')
	);

	protected $hasMany = array(
		'children' => array('model' => 'Section', 'foreignKey' => 'parent')
	);

	protected $indexes = array(
		array('columns' => array('site_id', 'handle'), 'unique' => true),
	);

	/**
	 * Returns an instance of the specified model
	 * @return object The model instance
	 * @static
	 */
	public static function model($class = __CLASS__)
	{
		return parent::model($class);
	}
}
