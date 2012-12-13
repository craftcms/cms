<?php
namespace Blocks;

/**
 * Handles user management related tasks.
 */
class UsersController extends BaseEntityController
{
	/**
	 * Returns the block service instance.
	 *
	 * @return UsersService
	 */
	protected function getService()
	{
		return blx()->users;
	}

	/**
	 * Saves a user's profile.
	 */
	public function actionSaveProfile()
	{
		$this->requirePostRequest();

		$userId = blx()->request->getRequiredPost('userId');

		if ($userId != blx()->user->getUser()->id)
		{
			blx()->user->requirePermission('editUsers');
		}

		$user = blx()->accounts->getUserById($userId);

		if (!$user)
		{
			throw new Exception(Blocks::t('No user exists with the ID “{id}”.', array('id' => $userId)));
		}

		$user->firstName = blx()->request->getPost('firstName');
		$user->lastName = blx()->request->getPost('lastName');

		$user->setContent(blx()->request->getPost('blocks'));

		$userSaved = blx()->accounts->saveUser($user);
		$profileSaved = blx()->users->saveProfile($user);

		if ($userSaved && $profileSaved)
		{
			blx()->user->setNotice(blocks::t('Profile saved.'));
			$this->redirectToPostedUrl();
		}
		else
		{
			blx()->user->setError(Blocks::t('Couldn’t save profile.'));
		}

		$this->renderRequestedTemplate(array(
			'account' => $user,
		));
	}

	/**
	 * Upload a user photo.
	 */
	public function actionUploadUserPhoto()
	{
		$this->requireAjaxRequest();
		$this->requireLogin();
		$userId = blx()->request->getRequiredQuery('userId');

		if ($userId != blx()->user->getUser()->id)
		{
			blx()->user->requirePermission('editUsers');
		}

		// Upload the file and drop it in the temporary folder
		$uploader = new \qqFileUploader();

		try
		{
			// Make sure a file was uploaded
			if ($uploader->file && $uploader->file->getSize())
			{
				$user = blx()->accounts->getUserById($userId);

				$folderPath = blx()->path->getTempUploadsPath().'userphotos/'.$user->username.'/';

				IOHelper::clearFolder($folderPath);

				IOHelper::ensureFolderExists($folderPath);
				$fileName = IOHelper::cleanFilename($uploader->file->getName());

				$uploader->file->save($folderPath.$fileName);

				// Test if we will be able to perform image actions on this image
				if (!blx()->images->setMemoryForImage($folderPath.$fileName))
				{
					IOHelper::deleteFile($folderPath.$fileName);
					$this->returnErrorJson(Blocks::t('The uploaded image is too large'));
				}

				blx()->images->cleanImage($folderPath.$fileName);

				$constraint = 500;
				list ($width, $height) = getimagesize($folderPath.$fileName);

				// If the file is in the format badscript.php.gif perhaps.
				if ($width && $height)
				{
					// Never scale up the images, so make the scaling factor always <= 1
					$factor = min($constraint / $width, $constraint / $height, 1);

					$html = blx()->templates->render('_components/tools/cropper_modal',
						array(
							'imageUrl' => UrlHelper::getResourceUrl('userphotos/temp/'.$user->username.'/'.$fileName),
							'width' => round($width * $factor),
							'height' => round($height * $factor),
							'factor' => $factor
						)
					);

					$this->returnJson(array('html' => $html));
				}
			}
		}
		catch (Exception $exception)
		{
			$this->returnErrorJson($exception->getMessage());
		}

		$this->returnErrorJson(Blocks::t('There was an error uploading your photo'));
	}

	/**
	 * Crop user photo.
	 */
	public function actionCropUserPhoto()
	{
		$this->requireAjaxRequest();
		$this->requireLogin();

		$userId = blx()->request->getRequiredPost('userId');

		if ($userId != blx()->user->getUser()->id)
		{
			blx()->user->requirePermission('editUsers');
		}

		try
		{
			$x1 = blx()->request->getRequiredPost('x1');
			$x2 = blx()->request->getRequiredPost('x2');
			$y1 = blx()->request->getRequiredPost('y1');
			$y2 = blx()->request->getRequiredPost('y2');
			$source = blx()->request->getRequiredPost('source');

			// Strip off any querystring info, if any.
			if (($qIndex = strpos($source, '?')) !== false)
			{
				$source = substr($source, 0, strpos($source, '?'));
			}

			$user = blx()->accounts->getUserById($userId);

			// make sure that this is this user's file
			$imagePath = blx()->path->getTempUploadsPath().'userphotos/'.$user->username.'/'.$source;

			if (IOHelper::fileExists($imagePath) && blx()->images->setMemoryForImage($imagePath))
			{
				blx()->users->deleteUserPhoto($user);
				if (blx()->users->cropAndSaveUserPhoto($imagePath, $x1, $x2, $y1, $y2, $user))
				{
					IOHelper::clearFolder(blx()->path->getTempUploadsPath().'userphotos/'.$user->username);
					$this->returnJson(array('success' => true));
				}
			}
			IOHelper::clearFolder(blx()->path->getTempUploadsPath().'userphotos/'.$user->username);
		}
		catch (Exception $exception)
		{
			$this->returnErrorJson($exception->getMessage());
		}

		$this->returnErrorJson(Blocks::t('Something went wrong when processing the photo.'));
	}

	/**
	 * Delete all the photos for current user.
	 */
	public function actionDeleteUserPhoto()
	{
		$this->requireAjaxRequest();
		$this->requireLogin();
		$userId = blx()->request->getRequiredPost('userId');

		if ($userId != blx()->user->getUser()->id)
		{
			blx()->user->requirePermission('editUsers');
		}

		$user = blx()->accounts->getUserById($userId);
		blx()->users->deleteUserPhoto($user);

		$record = UserRecord::model()->findById($user->id);
		$record->photo = null;
		$record->save();

		$this->returnJson(array('success' => true));
	}

	/**
	 * Saves a user's admin settings.
	 */
	public function actionSaveUserGroups()
	{
		$this->requirePostRequest();
		blx()->user->requirePermission('administrateUsers');

		$userId = blx()->request->getRequiredPost('userId');
		$groupIds = blx()->request->getPost('groups');

		blx()->userGroups->assignUserToGroups($userId, $groupIds);

		blx()->user->setNotice(Blocks::t('User groups saved.'));
		$this->redirectToPostedUrl();
	}

	/**
	 * Saves a user's admin settings.
	 */
	public function actionSaveUserPermissions()
	{
		$this->requirePostRequest();
		blx()->user->requirePermission('administrateUsers');

		$userId = blx()->request->getRequiredPost('userId');
		$user = blx()->accounts->getUserById($userId);

		if (!$user)
		{
			$this->_noUserExists($userId);
		}

		$user->admin = (bool)blx()->request->getPost('admin');
		blx()->accounts->saveUser($user);

		// Update the user permissions
		if ($user->admin)
		{
			$permissions = array();
		}
		else
		{
			$permissions = blx()->request->getPost('permissions');
		}

		blx()->userPermissions->saveUserPermissions($userId, $permissions);

		blx()->user->setNotice(Blocks::t('Permissions saved.'));
		$this->redirectToPostedUrl();
	}

	/**
	 * Sends a new verification email to a user.
	 */
	public function actionSendVerificationEmail()
	{
		$this->requirePostRequest();
		blx()->user->requirePermission('administrateUsers');

		$userId = blx()->request->getRequiredPost('userId');
		$user = blx()->accounts->getUserById($userId);

		if (!$user)
		{
			$this->_noUserExists($userId);
		}

		blx()->accounts->sendVerificationEmail($user);

		blx()->user->setNotice(Blocks::t('Verification email sent.'));
		$this->redirectToPostedUrl();
	}

	/**
	 * Activates a user, bypassing email verification.
	 */
	public function actionActivateUser()
	{
		$this->requirePostRequest();
		blx()->user->requirePermission('administrateUsers');

		$userId = blx()->request->getRequiredPost('userId');
		$user = blx()->accounts->getUserById($userId);

		if (!$user)
		{
			$this->_noUserExists($userId);
		}

		blx()->accounts->activateUser($user);

		blx()->user->setNotice(Blocks::t('User activated.'));
		$this->redirectToPostedUrl();
	}

	/**
	 * Unlocks a user, bypassing the cooldown phase.
	 */
	public function actionUnlockUser()
	{
		$this->requirePostRequest();
		blx()->user->requirePermission('administrateUsers');

		$userId = blx()->request->getRequiredPost('userId');
		$user = blx()->accounts->getUserById($userId);

		if (!$user)
		{
			$this->_noUserExists($userId);
		}

		blx()->accounts->unlockUser($user);

		blx()->user->setNotice(Blocks::t('User activated.'));
		$this->redirectToPostedUrl();
	}

	/**
	 * Suspends a user.
	 */
	public function actionSuspendUser()
	{
		$this->requirePostRequest();
		blx()->user->requirePermission('administrateUsers');

		$userId = blx()->request->getRequiredPost('userId');
		$user = blx()->accounts->getUserById($userId);

		if (!$user)
		{
			$this->_noUserExists($userId);
		}

		blx()->accounts->suspendUser($user);

		blx()->user->setNotice(Blocks::t('User suspended.'));
		$this->redirectToPostedUrl();
	}

	/**
	 * Unsuspends a user.
	 */
	public function actionUnsuspendUser()
	{
		$this->requirePostRequest();
		blx()->user->requirePermission('administrateUsers');

		$userId = blx()->request->getRequiredPost('userId');
		$user = blx()->accounts->getUserById($userId);

		if (!$user)
		{
			$this->_noUserExists($userId);
		}

		blx()->accounts->unsuspendUser($user);

		blx()->user->setNotice(Blocks::t('User unsuspended.'));
		$this->redirectToPostedUrl();
	}

	/**
	 * Archives a user.
	 */
	public function actionArchiveUser()
	{
		$this->requirePostRequest();
		blx()->user->requirePermission('administrateUsers');

		$userId = blx()->request->getRequiredPost('userId');
		$user = blx()->accounts->getUserById($userId);

		if (!$user)
		{
			$this->_noUserExists($userId);
		}

		blx()->accounts->archiveUser($user);

		blx()->user->setNotice(Blocks::t('User deleted.'));
		$this->redirectToPostedUrl();
	}

	/**
	 * Throws a "no user exists" exception
	 *
	 * @access private
	 * @param int $userId
	 * @throws Exception
	 */
	private function _noUserExists($userId)
	{
		throw new Exception(Blocks::t('No user exists with the ID “{id}”.', array('id' => $userId)));
	}
}
