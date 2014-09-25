<?php
namespace Craft;

/**
 * Class UsersFieldType
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.fieldtypes
 * @since     1.0
 */
class UsersFieldType extends BaseElementFieldType
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
