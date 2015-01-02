<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\variables;

/**
 * Field type template variable.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class FieldTypeVariable extends BaseComponentTypeVariable
{
	// Public Methods
	// =========================================================================

	/**
	 * Returns the field's input HTML.
	 *
	 * @param string $handle
	 * @param mixed  $value
	 *
	 * @return string
	 */
	public function getInputHtml($handle, $value)
	{
		return $this->component->getInputHtml($handle, $value);
	}

	/**
	 * Returns static HTML for the field's value.
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	public function getStaticHtml($value)
	{
		return $this->component->getStaticHtml($value);
	}
}
