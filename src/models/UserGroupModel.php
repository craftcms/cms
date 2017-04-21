<?php
namespace Craft;

craft()->requireEdition(Craft::Pro);

/**
 * User group model class.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.models
 * @since     1.0
 */
class UserGroupModel extends BaseModel
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
		return (string)Craft::t($this->name);
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
			return craft()->userPermissions->doesGroupHavePermission($this->id, $permission);
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
