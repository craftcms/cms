<?php
namespace Craft;

craft()->requireEdition(Craft::Client);

/**
 * Rebranding functions.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.variables
 * @since     1.0
 */
class RebrandVariable
{
	// Properties
	// =========================================================================

	/**
	 * @var
	 */
	private $_paths = array();

	/**
	 * @var
	 */
	private $_imageVariables = array();

	// Public Methods
	// =========================================================================

	/**
	 * Returns whether a custom logo has been uploaded.
	 *
	 * @return bool
	 */
	public function isLogoUploaded()
	{
		return $this->isImageUploaded('logo');
	}

	/**
	 * Returns whether a custom site icon has been uploaded.
	 *
	 * @return bool
	 */
	public function isIconUploaded()
	{
		return $this->isImageUploaded('icon');
	}

	/**
	 * Return whether the specified type of image has been uploaded for the site.
	 *
	 * @param string $type 'logo' or 'icon'.
	 *
	 * @return bool
	 */
	public function isImageUploaded($type)
	{
		return in_array($type, array('logo', 'icon')) && ($this->_getImagePath($type) !== false);
	}

	/**
	 * Returns the logo's Image variable, or null if a logo hasn't been uploaded.
	 *
	 * @return ImageVariable|null
	 */
	public function getLogo()
	{
		return $this->getImageVariable('logo');
	}

	/**
	 * Returns the icons variable, or null if a site icon hasn't been uploaded.
	 *
	 * @return ImageVariable|null
	 */
	public function getIcon()
	{
		return $this->getImageVariable('icon');
	}

	/**
	 * Get the ImageVariable for type.
	 *
	 * @param $type
	 *
	 * @return ImageVariable|null
	 */
	public function getImageVariable($type)
	{
		if (!in_array($type, array('logo', 'icon')))
		{
			return null;
		}

		if (!isset($this->_imageVariables[$type]))
		{
			$path = $this->_getImagePath($type);

			if ($path !== false)
			{
				$url = $this->_getImageUrl($path, $type);
				$this->_imageVariables[$type] = new ImageVariable($path, $url);
			}
			else
			{
				$this->_imageVariables[$type] = false;
			}
		}

		return $this->_imageVariables[$type] ?: null;
	}

	// Private Methods
	// =========================================================================

	/**
	 * Returns the path to a rebrand image by type or false if it hasn't ben uploaded.
	 *
	 * @param string $type logo or image.
	 *
	 * @return string
	 */
	private function _getImagePath($type)
	{
		if (!isset($this->_paths[$type]))
		{
			$files = IOHelper::getFolderContents(craft()->path->getRebrandPath().$type.'/', false);

			if (!empty($files))
			{
				$this->_paths[$type] = $files[0];
			}
			else
			{
				$this->_paths[$type] = false;
			}
		}

		return $this->_paths[$type];
	}

	/**
	 * Returns the URL to a rebrand image.
	 *
	 * @param $path
	 * @param $type
	 *
	 * @return string
	 */
	private function _getImageUrl($path, $type)
	{
		return UrlHelper::getResourceUrl('rebrand/'.$type.'/'.IOHelper::getFileName($path));
	}
}
