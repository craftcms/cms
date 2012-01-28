<?php

/**
 *
 */
class bSection extends bBaseModel
{
	protected $tableName = 'sections';

	protected $attributes = array(
		'name'        => array('type' => bAttributeType::String, 'maxLength' => 500, 'required' => true),
		'handle'      => array('type' => bAttributeType::String, 'maxLength' => 150, 'required' => true),
		'url_format'  => array('type' => bAttributeType::String),
		'max_entries' => array('type' => bAttributeType::Integer, 'unsigned' => true),
		'template'    => array('type' => bAttributeType::String, 'maxLength' => 500),
		'sortable'    => array('type' => bAttributeType::Boolean, 'required' => true, 'unsigned' => true)
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
