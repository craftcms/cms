<?php
namespace Craft;

/**
 *
 */
class CacheValueModel extends BaseModel
{
	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'unHashedKey'   => array(AttributeType::String),
			//'dateUpdated'   => array(AttributeType::DateTime, 'required' => true),
			'category'      => array(AttributeType::String, 'required' => true),
			'options'       => AttributeType::Mixed,
			'value'         => array(AttributeType::Mixed, 'required' => true),
		);
	}
}
