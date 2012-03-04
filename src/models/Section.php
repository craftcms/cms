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

	protected $hasMany = array(
		'children' => array('model' => 'Section', 'foreignKey' => 'parent'),
		'entries'  => array('model' => 'Entry', 'foreignKey' => 'section')
	);

	protected $indexes = array(
		array('columns' => array('site_id', 'handle'), 'unique' => true),
	);

	/**
	 * Returns the content blocks assigned to this section
	 * @return array
	 */
	public function getBlocks()
	{
		$data = Blocks::app()->db->createCommand()
			->select('sb.required, b.id, b.name, b.handle, b.class, b.instructions')
			->from('{{sectionblocks}} sb')
			->join('{{contentblocks}} b', 'sb.block_id = b.id')
			->where('sb.section_id = :id', array(':id' => $this->id))
			->order('sb.sort_order')
			->queryAll();

		$blocks = ContentBlock::model()->populateRecords($data);
		return $blocks;
	}
}
