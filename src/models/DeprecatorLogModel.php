<?php
namespace Craft;

/**
 * Validates the required User attributes for the installer.
 */
class DeprecatorLogModel extends BaseModel
{
	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'id'               => array(AttributeType::Number, 'required' => true),
			'key'              => array(AttributeType::String, 'required' => true),
			'origin'           => array(AttributeType::String, 'required' => true),
			'fingerprint'      => array(AttributeType::String, 'required' => true),
			'message'          => array(AttributeType::String, 'required' => true),
			'deprecatedSince'  => array(AttributeType::String, 'required' => true),
			'stackTrace'       => array(AttributeType::String, 'required' => true),
			'file'             => array(AttributeType::String, 'required' => true),
			'line'             => array(AttributeType::Number, 'required' => true),
			'method'           => array(AttributeType::String),
			'class'            => array(AttributeType::String),
			'dateCreated'      => array(AttributeType::DateTime, 'required' => true),
		);
	}
}
