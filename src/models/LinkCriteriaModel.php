<?php
namespace Blocks;

/**
 *
 */
class LinkCriteriaModel extends BaseModel
{
	/**
	 * @return array
	 */
	public function defineAttributes()
	{
		return array(
			'id'             => AttributeType::Number,
			'ltrHandle'      => AttributeType::String,
			'rtlHandle'      => AttributeType::String,
			'leftEntryType'  => AttributeType::ClassName,
			'rightEntryType' => AttributeType::ClassName,
			'leftSettings'   => AttributeType::Mixed,
			'rightSettings'  => AttributeType::Mixed,
		);
	}

	/**
	 * Returns the links created with this criteria.
	 *
	 * @return array
	 */
	public function getLinks()
	{
		if ($this->id)
		{
			return blx()->links->getLinksByCriteriaId($this->id);
		}
		else
		{
			return array();
		}
	}
}
