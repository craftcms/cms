<?php
namespace Craft;

/**
 *
 */
class LinkCriteriaModel extends BaseModel
{
	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'id'             => AttributeType::Number,
			'ltrHandle'      => AttributeType::String,
			'rtlHandle'      => AttributeType::String,
			'leftElementType'  => AttributeType::ClassName,
			'rightElementType' => AttributeType::ClassName,
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
			return craft()->links->getLinksByCriteriaId($this->id);
		}
		else
		{
			return array();
		}
	}
}
