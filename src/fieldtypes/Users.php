<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\fieldtypes;

use Craft;
use craft\app\elements\User;

/**
 * Users fieldtype
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Users extends BaseElementFieldType
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function getName()
	{
		return Craft::t('app', 'Users');
	}

	/**
	 * @inheritdoc
	 * @return User
	 */
	public function getElementClass()
	{
		return User::className();
	}

	/**
	 * @inheritdoc
	 */
	public function getAddButtonLabel()
	{
		return Craft::t('app', 'Add a user');
	}
}
