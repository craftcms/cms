<?php
namespace Craft;

/**
 * Field type template variable.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.variables
 * @since     1.0
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
