<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\fieldtypes;

use Craft;

/**
 * Users fieldtype
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Users extends BaseElementFieldType
{
	// Properties
	// =========================================================================

	/**
	 *  The element type this field deals with.
	 *
	 * @var string $elementType
	 */
	protected $elementType = 'User';

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseElementFieldType::getAddButtonLabel()
	 *
	 * @return string
	 */
	protected function getAddButtonLabel()
	{
		return Craft::t('Add a user');
	}
}
