<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\variables;

use craft\app\Craft;

craft()->requireEdition(Craft::Client);

/**
 * Rebranding functions.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class RebrandVariable
{
	// Properties
	// =========================================================================

	/**
	 * @var
	 */
	private $_logoPath;

	/**
	 * @var
	 */
	private $_logoVariable;

	// Public Methods
	// =========================================================================

	/**
	 * Returns whether a custom logo has been uploaded.
	 *
	 * @return bool
	 */
	public function isLogoUploaded()
	{
		return ($this->_getLogoPath() !== false);
	}

	/**
	 * Returns the logo variable, or false if a logo hasn't been uploaded.
	 *
	 * @return LogoVariable
	 */
	public function getLogo()
	{
		if (!isset($this->_logoVariable))
		{
			$logoPath = $this->_getLogoPath();

			if ($logoPath !== false)
			{
				$this->_logoVariable = new LogoVariable($logoPath);
			}
			else
			{
				$this->_logoVariable = false;
			}
		}

		return $this->_logoVariable;
	}

	// Public Methods
	// =========================================================================

	/**
	 * Returns the path to the logo, or false if a logo hasn't been uploaded.
	 *
	 * @return string
	 */
	private function _getLogoPath()
	{
		if (!isset($this->_logoPath))
		{
			$files = IOHelper::getFolderContents(craft()->path->getStoragePath().'logo/', false);
			if (!empty($files))
			{
				$this->_logoPath = $files[0];
			}
			else
			{
				$this->_logoPath = false;
			}
		}

		return $this->_logoPath;
	}
}
