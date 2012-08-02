<?php
namespace Blocks;

/**
 * Validates the required Site attributes for the installer.
 */
class InstallSiteForm extends BaseForm
{
	protected $attributes = array(
		'sitename' => AttributeType::Name,
		'url'      => array('type' => AttributeType::Url, 'required' => true)
	);
}
