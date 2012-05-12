<?php
namespace Blocks;

/**
 * Validates the required Site attributes for the installer.
 */
class InstallSiteForm extends FormModel
{
	protected $attributes = array(
		'language' => AttributeType::Language,
		'sitename' => AttributeType::Name,
		'url'      => array('type' => AttributeType::Url, 'required' => true)
	);
}
