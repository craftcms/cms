<?php
namespace Blocks;

/**
 * Block model base class
 *
 * @abstract
 */
abstract class BaseBlockModel extends BaseModel
{
	protected function getProperties()
	{
		return array(
			'name'         => PropertyType::Name,
			'handle'       => array(PropertyType::Handle, 'reservedWords' => 'id,date_created,date_updated,uid,title'),
			'instructions' => PropertyType::Text,
			'required'     => PropertyType::Boolean,
			'translatable' => PropertyType::Boolean,
			'class'        => PropertyType::ClassName,
			'settings'     => PropertyType::Json,
			'sort_order'   => PropertyType::SortOrder,
		);
	}
}
