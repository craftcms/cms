<?php
namespace Craft;

/**
 * Single-select option field data class.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.fieldtypes
 * @since     1.0
 */
class SingleOptionFieldData extends OptionData
{
	// Properties
	// =========================================================================

	/**
	 * @var
	 */
	private $_options;

	// Public Methods
	// =========================================================================

	/**
	 * Returns the options.
	 *
	 * @return array|null
	 */
	public function getOptions()
	{
		return $this->_options;
	}

	/**
	 * Sets the options.
	 *
	 * @param array $options
	 *
	 * @return null
	 */
	public function setOptions($options)
	{
		$this->_options = $options;
	}
}
