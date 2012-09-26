<?php
namespace Blocks;

/**
 *
 */
class UserProfilesService extends BaseApplicationComponent
{
	/**
	 * Populates a user profile package.
	 *
	 * @param array|UserProfileRecord $attributes
	 * @return UserProfileModel
	 */
	public function populateProfilePackage($attributes)
	{
		if ($attributes instanceof UserProfileRecord)
		{
			$attributes = $attributes->getAttributes();
		}

		$profile = new UserProfileModel();

		$profile->id = $attributes['id'];
		$profile->userId = $attributes['userId'];
		$profile->firstName = $attributes['firstName'];
		$profile->lastName = $attributes['lastName'];
		$profile->blocks = $attributes['blocks'];

		return $profile;
	}

	/**
	 * Gets a user profile by its ID.
	 *
	 * @param int $profileId
	 * @return UserProfileModel
	 */
	public function getProfileById($profileId)
	{
		$profileRecord = UserProfileRecord::model()->findById($profileId);
		if ($profileRecord)
		{
			return $this->populateProfilePackage($profileRecord);
		}
	}

	/**
	 * Gets a user profile by its user ID.
	 *
	 * @param int $userId
	 * @return UserProfileModel
	 */
	public function getProfileByUserId($userId)
	{
		$profileRecord = UserProfileRecord::model()->findByAttributes(array(
			'userId' => $userId
		));

		if ($profileRecord)
		{
			return $this->populateProfilePackage($profileRecord);
		}
	}

	/**
	 * Saves a user profile.
	 *
	 * @param UserProfileModel $profile
	 * @return bool
	 */
	public function saveProfile(UserProfileModel $profile)
	{
		$profileRecord = $this->_getProfileRecordByUserId($profile->userId);

		if ($profileRecord->isNewRecord())
		{
			$profileRecord->userId = $profile->userId;
		}

		$profileRecord->firstName = $profile->firstName;
		$profileRecord->lastName = $profile->lastName;

		// Populate the blocks' content
		$blocks = blx()->userProfileBlocks->getAllBlocks();

		foreach ($blocks as $block)
		{
			$handle = $block->handle;
			$name = 'block'.$block->id;

			if (isset($profile->blocks[$name]))
			{
				$profileRecord->$handle = $profile->blocks[$name];
			}
			else
			{
				$profileRecord->$handle = null;
			}
		}

		if ($profileRecord->save())
		{
			// Now that we have a profile ID, save it on the package
			if (!$profile->id)
			{
				$profile->id = $profileRecord->id;
			}

			return true;
		}
		else
		{
			$profile->addErrors($profileRecord->getErrors());

			return false;
		}
	}

	/**
	 * Gets a profile's record by its user ID.
	 *
	 * @access private
	 * @param int $userId
	 * @return UserProfileRecord
	 */
	private function _getProfileRecordByUserId($userId)
	{
		$profileRecord = UserProfileRecord::model()->findByAttributes(array(
			'userId' => $userId
		));

		if (!$profileRecord)
		{
			$profileRecord = new UserProfileRecord();
			$profileRecord->userId = $userId;
		}

		return $profileRecord;
	}

	/**
	 * Throws a "No profile exists" exception.
	 *
	 * @access private
	 * @param int $profileId
	 * @throws Exception
	 */
	private function _noProfileExists($profileId)
	{
		throw new Exception(Blocks::t('No profile exists with the ID “{id}”', array('id' => $profileId)));
	}
}
