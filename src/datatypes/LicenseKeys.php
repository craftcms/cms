<?php

class LicenseKeys extends BlocksDataType
{
	private static $attributes = array(
		'key' => array('type' => AttributeTypes::String, 'maxLength' => 36, 'required' => true)
	);
}
