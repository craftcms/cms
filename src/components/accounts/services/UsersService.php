<?php
namespace Blocks;

/**
 *
 */
class UsersService extends BaseEntityService
{
	// -------------------------------------------
	//  User Profile Blocks
	// -------------------------------------------

	/**
	 * The block model class name.
	 *
	 * @access protected
	 * @var string
	 */
	protected $blockModelClass = 'UserProfileBlockModel';

	/**
	 * The block record class name.
	 *
	 * @access protected
	 * @var string
	 */
	protected $blockRecordClass = 'UserProfileBlockRecord';

	/**
	 * The content record class name.
	 *
	 * @access protected
	 * @var string
	 */
	protected $contentRecordClass = 'UserProfileRecord';

	/**
	 * The name of the content table column right before where the block columns should be inserted.
	 *
	 * @access protected
	 * @var string
	 */
	protected $placeBlockColumnsAfter = 'userId';

	// -------------------------------------------
	//  User Profiles
	// -------------------------------------------

	/**
	 * Saves a user profile.
	 *
	 * @param UserModel $user
	 * @return bool
	 */
	public function saveProfile(UserModel $user)
	{
		$profileRecord = $this->getProfileRecordByUserId($user->id);

		// Populate the blocks' content
		$blocks = $this->getAllBlocks();
		$blockTypes = array();

		foreach ($blocks as $block)
		{
			$blockType = blx()->blockTypes->populateBlockType($block);
			$blockType->entity = $user;

			if ($blockType->defineContentAttribute() !== false)
			{
				$handle = $block->handle;
				$profileRecord->$handle = $blockType->getPostData();
			}

			// Keep the block type instance around for calling onAfterEntitySave()
			$blockTypes[] = $blockType;
		}

		if ($profileRecord->save())
		{
			// Give the block types a chance to do any post-processing
			foreach ($blockTypes as $blockType)
			{
				$blockType->onAfterEntitySave();
			}

			return true;
		}
		else
		{
			$user->addErrors($profileRecord->getErrors());

			return false;
		}
	}

	/**
	 * Gets a profile's record by its user ID.
	 *
	 * @param int $userId
	 * @return UserProfileRecord
	 */
	public function getProfileRecordByUserId($userId)
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

	// -------------------------------------------
	//  Users
	// -------------------------------------------

	/**
	 * Finds users.
	 *
	 * @param UserCriteria|null $criteria
	 * @return array
	 */
	public function findUsers(UserCriteria $criteria = null)
	{
		if (!$criteria)
		{
			$criteria = new UserCriteria();
		}

		$query = blx()->db->createCommand()
			->select('u.*')
			->from('users u');

		$this->_applyUserConditions($query, $criteria);

		if ($criteria->order)
		{
			$query->order($criteria->order);
		}

		if ($criteria->offset)
		{
			$query->offset($criteria->offset);
		}

		if ($criteria->limit)
		{
			$query->limit($criteria->limit);
		}

		$result = $query->queryAll();
		return UserModel::populateModels($result, $criteria->indexBy);
	}

	/**
	 * Finds a user.
	 *
	 * @param UserCriteria|null $criteria
	 * @return array
	 */
	public function findUser(UserCriteria $criteria = null)
	{
		if (!$criteria)
		{
			$criteria = new UserCriteria();
		}

		$query = blx()->db->createCommand()
			->select('u.*')
			->from('users u');

		$this->_applyUserConditions($query, $criteria);

		$result = $query->queryRow();

		if ($result)
		{
			return UserModel::populateModel($result);
		}
	}

	/**
	 * Gets the total number of users.
	 *
	 * @param array $criteria
	 * @return int
	 * @return int
	 */
	public function getTotalUsers($criteria = array())
	{
		if (!$criteria)
		{
			$criteria = new UserCriteria();
		}

		$query = blx()->db->createCommand()
			->select('count(u.id)')
			->from('users u');

		$this->_applyUserConditions($query, $criteria);

		return (int) $query->queryScalar();
	}

	/**
	 * Applies WHERE conditions to a DbCommand query for users.
	 *
	 * @access private
	 * @param DbCommand $query
	 * @param           $criteria
	 * @param array     $criteria
	 */
	private function _applyUserConditions($query, $criteria)
	{
		$whereConditions = array();
		$whereParams = array();

		if ($criteria->id)
		{
			$whereConditions[] = DbHelper::parseParam('u.id', $criteria->id, $whereParams);
		}

		if ($criteria->groupId || $criteria->group)
		{
			$query->join('usergroups_users gu', 'gu.userId = u.id');

			if ($criteria->groupId)
			{
				$whereConditions[] = DbHelper::parseParam('gu.groupId', $criteria->groupId, $whereParams);
			}

			if ($criteria->group)
			{
				$query->join('usergroups g', 'g.id = gu.groupId');
				$whereConditions[] = DbHelper::parseParam('g.handle', $criteria->group, $whereParams);
			}
		}

		if ($criteria->username)
		{
			$whereConditions[] = DbHelper::parseParam('u.username', $criteria->username, $whereParams);
		}

		if ($criteria->firstName)
		{
			$whereConditions[] = DbHelper::parseParam('u.firstName', $criteria->firstName, $whereParams);
		}

		if ($criteria->lastName)
		{
			$whereConditions[] = DbHelper::parseParam('u.lastName', $criteria->lastName, $whereParams);
		}

		if ($criteria->email)
		{
			$whereConditions[] = DbHelper::parseParam('u.email', $criteria->email, $whereParams);
		}

		if ($criteria->admin)
		{
			$whereConditions[] = DbHelper::parseParam('u.admin', 1, $whereParams);
		}

		if ($criteria->status && $criteria->status != '*')
		{
			$whereConditions[] = DbHelper::parseParam('u.status', $criteria->status, $whereParams);
		}

		if ($criteria->lastLoginDate)
		{
			$whereConditions[] = DbHelper::parseParam('u.lastLoginDate', $criteria->lastLoginDate, $whereParams);
		}

		if ($whereConditions)
		{
			array_unshift($whereConditions, 'and');
			$query->where($whereConditions, $whereParams);
		}
	}

	/**
	 * Crop and save a user's photo by coordinates for a given user model.
	 *
	 * @param $source
	 * @param $x1
	 * @param $x2
	 * @param $y1
	 * @param $y2
	 * @param UserModel $user
	 * @return bool
	 * @throws \Exception
	 */
	public function cropAndSaveUserPhoto($source, $x1, $x2, $y1, $y2, UserModel $user)
	{
		$userPhotoFolder = blx()->path->getUserPhotosPath().$user->username.'/';
		$targetFolder = $userPhotoFolder.'original/';

		IOHelper::ensureFolderExists($userPhotoFolder);
		IOHelper::ensureFolderExists($targetFolder);

		$filename = pathinfo($source, PATHINFO_BASENAME);
		$targetPath = $targetFolder . $filename;


		$image = blx()->images->loadImage($source);
		$image->crop($x1, $x2, $y1, $y2);
		$result = $image->saveAs($targetPath);

		if ($result)
		{
			IOHelper::changePermissions($targetPath, IOHelper::writableFilePermissions);
			$record = UserRecord::model()->findById($user->id);
			$record->photo = $filename;
			$record->save();

			$user->photo = $filename;

			return true;
		}

		return false;
	}

	/**
	 * Delete a user's photo.
	 *
	 * @param UserModel $user
	 * @return void
	 */
	public function deleteUserPhoto(UserModel $user)
	{
		$folder = blx()->path->getUserPhotosPath().$user->username;

		if (IOHelper::folderExists($folder))
		{
			IOHelper::deleteFolder($folder);
		}
	}
}
