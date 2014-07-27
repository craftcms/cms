<?php
namespace Craft;

craft()->requireEdition(Craft::Client);

/**
 * Class LogoVariable
 *
 * @package craft.app.validators
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
