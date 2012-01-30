<?php

/**
 *
 */
class bSection extends bBaseModel
{
	protected $tableName = 'sections';

	protected $attributes = array(
		'name'        => bAttributeType::Name,
		'handle'      => bAttributeType::Handle,
		'url_format'  => bAttributeType::Varchar,
		'max_entries' => array('type' => bAttributeType::TinyInt, 'unsigned' => true),
		'template'    => bAttributeType::Template,
		'sortable'    => bAttributeType::Boolean
	);

	protected $belongsTo = array(
		'parent' => array('model' => 'bSection'),
		'site'   => array('model' => 'bSite', 'required' => true)
	);

	protected $hasBlocks = array(
		'blocks' => array('through' => 'bSectionBlock', 'foreignKey' => 'section')
	);

	protected $hasMany = array(
		'children' => array('model' => 'bSection', 'foreignKey' => 'parent')
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
