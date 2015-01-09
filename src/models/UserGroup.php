<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\models;

use Craft;
use craft\app\enums\AttributeType;

Craft::$app->requireEdition(Craft::Pro);

/**
 * UserGroup model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class UserGroup extends BaseModel
{
	// Public Methods
	// =========================================================================

	/**
	 * Use the translated group name as the string representation.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return Craft::t($this->name);
	}

	/**
	 * Returns whether the group has permission to perform a given action.
	 *
	 * @param string $permission
	 *
	 * @return bool
	 */
	public function can($permission)
	{
		if ($this->id)
		{
			return Craft::$app->userPermissions->doesGroupHavePermission($this->id, $permission);
		}
		else
		{
			return false;
		}
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseModel::defineAttributes()
	 *
	 * @return array
	 */
	protected function defineAttributes()
	{
		$attributes['id'] = AttributeType::Number;
		$attributes['name'] = AttributeType::String;
		$attributes['handle'] = AttributeType::String;

		return $attributes;
	}
}
