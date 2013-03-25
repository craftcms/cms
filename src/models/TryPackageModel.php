<?php
namespace Craft;

/**
 * Used to hold package trial data.
 */
class TryPackageModel extends BaseModel
{
	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'packageHandle' => array(AttributeType::String, 'required' => true),
			'success'       => AttributeType::Bool,
		);
	}
}
