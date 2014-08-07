<?php
namespace Craft;

/**
 * Class EntriesFieldType
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.etc.fieldtypes
 * @since     1.0
 */
class EntriesFieldType extends BaseElementFieldType
{
	// Properties
	// =========================================================================

	/**
	 * @var string $elementType The element type this field deals with.
	 */
	protected $elementType = 'Entry';

	// Protected Methods
	// =========================================================================

	/**
	 * Returns the label for the "Add" button.
	 *
	 * @return string
	 */
	protected function getAddButtonLabel()
	{
		return Craft::t('Add an entry');
	}
}
