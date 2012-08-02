<?php
namespace Blocks;

/**
 * Validates the required Site attributes for the installer.
 */
class InstallSiteForm extends BaseForm
{
	protected $attributes = array(
		'sitename' => array('type' => AttributeType::Name, 'required' => true),
		'url'      => array('type' => AttributeType::Url, 'required' => true)
	);
}
