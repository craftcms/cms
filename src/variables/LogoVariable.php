<?php
namespace Craft;

craft()->requirePackage(CraftPackage::Rebrand);

/**
 *
 */
class LogoVariable extends ImageVariable
{
	/**
	 * Return the URL to the logo.
	 *
	 * @return string|null
	 */
	public function getUrl()
	{
		return UrlHelper::getResourceUrl('logo/'.IOHelper::getFileName($this->path));
	}
}
