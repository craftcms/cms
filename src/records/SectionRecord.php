<?php
namespace Craft;

/**
 *
 */
class SectionRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'sections';
	}

	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'name'      => array(AttributeType::Name, 'required' => true),
			'handle'    => array(AttributeType::Handle, 'required' => true),
			'type'      => array(AttributeType::Enum, 'values' => array(SectionType::Single, SectionType::Channel, SectionType::Structure), 'default' => SectionType::Channel, 'required' => true),
			'hasUrls'   => array(AttributeType::Bool, 'default' => true),
			'template'  => AttributeType::Template,
			'maxLevels' => array(AttributeType::Number, 'min' => 1),
		);
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'locales' => array(static::HAS_MANY, 'SectionLocaleRecord', 'sectionId'),
		);
	}

	/**
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => array('name'), 'unique' => true),
			array('columns' => array('handle'), 'unique' => true),
		);
	}

	/**
	 * @return array
	 */
	public function scopes()
	{
		return array(
			'ordered' => array('order' => 'name'),
		);
	}
}
