<?php
namespace Blocks;

/**
 * User profile model class
 *
 * Used for transporting user profile data throughout the system.
 */
class UserProfileModel extends BaseModel
{
	public function defineAttributes()
	{
		return array(
			'id'        => AttributeType::Number,
			'userId'    => AttributeType::Number,
			'firstName' => AttributeType::String,
			'lastName'  => AttributeType::String,
			'blocks'    => AttributeType::Mixed,
		);
	}

	/**
	 * Saves the user profile.
	 *
	 * @return bool
	 */
	public function save()
	{
		return blx()->userProfiles->saveProfile($this);
	}

	/**
	 * Returns the user's full name (first+last name), if it's available.
	 *
	 * @return string
	 */
	public function fullName()
	{
		return $this->firstName . ($this->firstName && $this->lastName ? ' ' : '') . $this->lastName;
	}
}
