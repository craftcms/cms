<?php
namespace Blocks;

/**
 * Validates the required Site attributes for the installer.
 */
class InstallSiteForm extends BaseForm
{
	protected function getProperties()
	{
		return array(
			'siteName' => PropertyType::Name,
			'siteUrl'  => array(PropertyType::Url, 'required' => true)
		);
	}
}
