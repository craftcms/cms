<?php

class LicenseKeys extends BlocksModel
{
	private static $attributes = array(
		'key' => array('type' => AttributeTypes::String, 'maxLength' => 36, 'required' => true)
	);
}
