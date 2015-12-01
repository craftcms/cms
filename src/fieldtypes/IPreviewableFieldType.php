<?php
namespace Craft;

/**
 * Interface IPreviewableFieldType
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.fieldtypes
 * @since     1.0
 */
interface IPreviewableFieldType
{
	// Public Methods
	// =========================================================================

	/**
	 * Returns the HTML that should be shown for this field in Table View.
	 *
	 * @param mixed $value
	 *
	 * @return string|null
	 */
	public function getTableAttributeHtml($value);
}
