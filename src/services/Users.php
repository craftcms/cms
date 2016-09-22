<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\services;

use Craft;
use craft\app\dates\DateInterval;
use craft\app\dates\DateTime;
use craft\app\db\Query;
use craft\app\elements\Asset;
use craft\app\errors\ImageException;
use craft\app\errors\UserNotFoundException;
use craft\app\errors\VolumeException;
use craft\app\events\DeleteUserEvent;
use craft\app\events\UserActivateEvent;
use craft\app\events\UserAssignGroupEvent;
use craft\app\events\UserEvent;
use craft\app\events\UserGroupsAssignEvent;
use craft\app\events\UserSuspendEvent;
use craft\app\events\UserUnlockEvent;
use craft\app\events\UserUnsuspendEvent;
use craft\app\helpers\Assets as AssetsHelper;
use craft\app\helpers\DateTimeHelper;
use craft\app\helpers\Db;
use craft\app\helpers\Io;
use craft\app\helpers\Image;
use craft\app\helpers\Json;
use craft\app\helpers\StringHelper;
use craft\app\helpers\Template;
use craft\app\helpers\Url;
use craft\app\models\Password;
use craft\app\elements\User;
use craft\app\records\User as UserRecord;
use yii\base\Component;
use yii\db\Exception;

/**
 * The Users service provides APIs for managing users.
 *
 * An instance of the Users service is globally accessible in Craft via [[Application::users `Craft::$app->getUsers()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Users extends Component
{
    // Constants
    // =========================================================================

    /**
     * @event UserEvent The event that is triggered before a user is saved.
     *
     * You may set [[UserEvent::isValid]] to `false` to prevent the user from getting saved.
     */
    const EVENT_BEFORE_SAVE_USER = 'beforeSaveUser';

    /**
     * @event UserEvent The event that is triggered after a user is saved.
     */
    const EVENT_AFTER_SAVE_USER = 'afterSaveUser';

    /**
     * @event UserTokenEvent The event that is triggered before a user's email is verified.
     */
    const EVENT_BEFORE_VERIFY_EMAIL = 'beforeVerifyEmail';

    /**
     * @event UserTokenEvent The event that is triggered after a user's email is verified.
     */
    const EVENT_AFTER_VERIFY_EMAIL = 'afterVerifyEmail';

    /**
     * @event UserActivateEvent The event that is triggered before a user is activated.
     *
     * You may set [[UserActivateEvent::isValid]] to `false` to prevent the user from getting activated.
     */
    const EVENT_BEFORE_ACTIVATE_USER = 'beforeActivateUser';

    /**
     * @event UserEvent The event that is triggered after a user is activated.
     */
    const EVENT_AFTER_ACTIVATE_USER = 'afterActivateUser';

    /**
     * @event UserUnlockEvent The event that is triggered before a user is unlocked.
     *
     * You may set [[UserUnlockEvent::isValid]] to `false` to prevent the user from getting unlocked.
     */
    const EVENT_BEFORE_UNLOCK_USER = 'beforeUnlockUser';

    /**
     * @event UserUnlockEvent The event that is triggered after a user is unlocked.
     */
    const EVENT_AFTER_UNLOCK_USER = 'afterUnlockUser';

    /**
     * @event UserSuspendEvent The event that is triggered before a user is suspended.
     *
     * You may set [[UserSuspendEvent::isValid]] to `false` to prevent the user from getting suspended.
     */
    const EVENT_BEFORE_SUSPEND_USER = 'beforeSuspendUser';

    /**
     * @event UserSuspendEvent The event that is triggered after a user is suspended.
     */
    const EVENT_AFTER_SUSPEND_USER = 'afterSuspendUser';

    /**
     * @event UserUnsuspendEvent The event that is triggered before a user is unsuspended.
     *
     * You may set [[UserUnsuspendEvent::isValid]] to `false` to prevent the user from getting unsuspended.
     */
    const EVENT_BEFORE_UNSUSPEND_USER = 'beforeUnsuspendUser';

    /**
     * @event UserUnsuspendEvent The event that is triggered after a user is unsuspended.
     */
    const EVENT_AFTER_UNSUSPEND_USER = 'afterUnsuspendUser';

    /**
     * @event DeleteUserEvent The event that is triggered before a user is deleted.
     *
     * You may set [[UserEvent::isValid]] to `false` to prevent the user from getting deleted.
     */
    const EVENT_BEFORE_DELETE_USER = 'beforeDeleteUser';

    /**
     * @event DeleteUserEvent The event that is triggered after a user is deleted.
     */
    const EVENT_AFTER_DELETE_USER = 'afterDeleteUser';

    /**
     * @event UserEvent The event that is triggered before a user's password is set.
     *
     * The new password will be accessible from [[User::newPassword]].
     *
     * You may set [[UserEvent::isValid]] to `false` to prevent the user's password from getting set.
     */
    const EVENT_BEFORE_SET_PASSWORD = 'beforeSetPassword';

    /**
     * @event UserEvent The event that is triggered after a user's password is set.
     */
    const EVENT_AFTER_SET_PASSWORD = 'afterSetPassword';

    /**
     * @event AssignUserGroupEvent The event that is triggered before a user is assigned to some user groups.
     *
     * You may set [[AssignUserGroupEvent::isValid]] to `false` to prevent the user from getting assigned to the groups.
     */
    const EVENT_BEFORE_ASSIGN_USER_TO_GROUPS = 'beforeAssignUserToGroups';

    /**
     * @event AssignUserGroupEvent The event that is triggered after a user is assigned to some user groups.
     */
    const EVENT_AFTER_ASSIGN_USER_TO_GROUPS = 'afterAssignUserToGroups';

    /**
     * @event UserAssignGroupEvent The event that is triggered before a user is assigned to the default user group.
     *
     * You may set [[UserAssignGroupEvent::isValid]] to `false` to prevent the user from getting assigned to the default
     * user group.
     */
    const EVENT_BEFORE_ASSIGN_USER_TO_DEFAULT_GROUP = 'beforeAssignUserToDefaultGroup';

    /**
     * @event UserAssignGroupEvent The event that is triggered after a user is assigned to the default user group.
     */
    const EVENT_AFTER_ASSIGN_USER_TO_DEFAULT_GROUP = 'afterAssignUserToDefaultGroup';

    // Public Methods
    // =========================================================================

    /**
     * Returns a user by their ID.
     *
     * ```php
     * $user = Craft::$app->getUsers()->getUserById($userId);
     * ```
     *
     * @param integer $userId The user’s ID.
     *
     * @return User|null The user with the given ID, or `null` if a user could not be found.
     */
    public function getUserById($userId)
    {
        return Craft::$app->getElements()->getElementById($userId, User::class);
    }

    /**
     * Returns a user by their username or email.
     *
     * ```php
     * $user = Craft::$app->getUsers()->getUserByUsernameOrEmail($loginName);
     * ```
     *
     * @param string $usernameOrEmail The user’s username or email.
     *
     * @return User|null The user with the given username/email, or `null` if a user could not be found.
     */
    public function getUserByUsernameOrEmail($usernameOrEmail)
    {
        return User::find()
            ->where(
                ['or', 'username=:usernameOrEmail', 'email=:usernameOrEmail'],
                [':usernameOrEmail' => $usernameOrEmail]
            )
            ->withPassword()
            ->status(null)
            ->one();
    }

    /**
     * Returns a user by their email.
     *
     * ```php
     * $user = Craft::$app->getUsers()->getUserByEmail($email);
     * ```
     *
     * @param string $email The user’s email.
     *
     * @return User|null The user with the given email, or `null` if a user could not be found.
     */
    public function getUserByEmail($email)
    {
        return User::find()
            ->email($email)
            ->withPassword()
            ->one();
    }

    /**
     * Returns a user by their UID.
     *
     * ```php
     * $user = Craft::$app->getUsers()->getUserByUid($userUid);
     * ```
     *
     * @param integer $uid The user’s UID.
     *
     * @return User|null The user with the given UID, or `null` if a user could not be found.
     */
    public function getUserByUid($uid)
    {
        return User::find()
            ->uid($uid)
            ->status(null)
            ->enabledForSite(false)
            ->one();
    }

    /**
     * Returns whether a verification code is valid for the given user.
     *
     * This method first checks if the code has expired past the
     * [verificationCodeDuration](http://craftcms.com/docs/config-settings#verificationCodeDuration) config
     * setting. If it is still valid, then, the checks the validity of the contents of the code.
     *
     * @param User   $user The user to check the code for.
     * @param string $code The verification code to check for.
     *
     * @return boolean Whether the code is still valid.
     */
    public function isVerificationCodeValidForUser(User $user, $code)
    {
        $valid = false;
        $userRecord = $this->_getUserRecordById($user->id);

        if ($userRecord) {
            $minCodeIssueDate = DateTimeHelper::currentUTCDateTime();
            $duration = new DateInterval(Craft::$app->getConfig()->get('verificationCodeDuration'));
            $minCodeIssueDate->sub($duration);

            $valid = $userRecord->verificationCodeIssuedDate > $minCodeIssueDate;

            if (!$valid) {
                // It's expired, go ahead and remove it from the record so if they click the link again, it'll throw an
                // Exception.
                $userRecord = $this->_getUserRecordById($user->id);
                $userRecord->verificationCodeIssuedDate = null;
                $userRecord->verificationCode = null;
                $userRecord->save();
            } else {
                if (Craft::$app->getSecurity()->validatePassword($code, $userRecord->verificationCode)) {
                    $valid = true;
                } else {
                    $valid = false;
                    Craft::warning('The verification code ('.$code.') given for userId: '.$user->id.' does not match the hash in the database.', __METHOD__);
                }
            }
        } else {
            Craft::warning('Could not find a user with id:'.$user->id.'.', __METHOD__);
        }

        return $valid;
    }

    /**
     * Returns the “Client” user account, if it has been created yet.
     *
     * An exception will be thrown if this function is called from Craft Personal or Pro.
     *
     * ```php
     * if (Craft::$app->getEdition() == Craft::Client)
     * {
     *     $clientAccount = Craft::$app->getUsers()->getClient();
     * }
     * ```
     *
     * @return User|null The “Client” user account, or `null` if it hasn’t been created yet.
     */
    public function getClient()
    {
        Craft::$app->requireEdition(Craft::Client, false);

        return User::find()
            ->client(true)
            ->status(null)
            ->one();
    }

    /**
     * Saves a new or existing user.
     *
     * ```php
     * $user = new User();
     * $user->username  = 'tommy';
     * $user->firstName = 'Tom';
     * $user->lastName  = 'Foolery';
     * $user->email     = 'tom@thefoolery.com';
     *
     * $user->getContent()->birthYear = 1812;
     *
     * $success = Craft::$app->getUsers()->saveUser($user);
     *
     * if (!$success)
     * {
     *     Craft::error('Couldn’t save the user "'.$user->username.'"', __METHOD__);
     * }
     * ```
     *
     * @param User $user The user to be saved.
     *
     * @return boolean Whether the user was saved successfully
     * @throws UserNotFoundException
     * @throws \Exception if reasons
     */
    public function saveUser(User $user)
    {
        $isNewUser = !$user->id;

        if (!$isNewUser) {
            $userRecord = $this->_getUserRecordById($user->id);

            if (!$userRecord) {
                throw new UserNotFoundException("No user exists with the ID '{$user->id}'");
            }
        } else {
            $userRecord = new UserRecord();
            $userRecord->pending = true;
            $userRecord->locked = $user->locked;
            $userRecord->suspended = $user->suspended;
            $userRecord->pending = $user->pending;
            $userRecord->archived = $user->archived;
        }

        // Set the user record attributes
        $userRecord->username = $user->username;
        $userRecord->firstName = $user->firstName;
        $userRecord->lastName = $user->lastName;
        $userRecord->photoId = $user->photoId;
        $userRecord->email = $user->email;
        $userRecord->admin = $user->admin;
        $userRecord->client = $user->client;
        $userRecord->passwordResetRequired = $user->passwordResetRequired;
        $userRecord->unverifiedEmail = $user->unverifiedEmail;

        $userRecord->validate();
        $user->addErrors($userRecord->getErrors());

        if (Craft::$app->getEdition() == Craft::Pro) {
            // Validate any content.
            Craft::$app->getContent()->validateContent($user);
        }

        // If newPassword is set at all, even to an empty string, validate & set it.
        if ($user->newPassword !== null) {
            $this->_setPasswordOnUserRecord($user, $userRecord);
        }

        if ($user->hasErrors()) {
            return false;
        }

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            // Fire a 'beforeSaveUser' event
            $event = new UserEvent([
                'user' => $user,
                'isNew' => $isNewUser
            ]);

            $this->trigger(self::EVENT_BEFORE_SAVE_USER, $event);

            // Is the event is giving us the go-ahead?
            if ($event->isValid) {
                // Save the element
                $success = Craft::$app->getElements()->saveElement($user, false);

                // If it didn't work, rollback the transaction in case something changed in onBeforeSaveUser
                if (!$success) {
                    $transaction->rollBack();

                    return false;
                }

                // Now that we have an element ID, save it on the other stuff
                if ($isNewUser) {
                    $userRecord->id = $user->id;
                }

                $userRecord->save(false);
            } else {
                $success = false;
            }

            // Commit the transaction regardless of whether we saved the user, in case something changed
            // in onBeforeSaveUser
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();

            throw $e;
        }

        if ($success) {
            // Fire an 'afterSaveUser' event
            $this->trigger(self::EVENT_AFTER_SAVE_USER, new UserEvent([
                'user' => $user,
                'isNew' => $isNewUser
            ]));

            // They got unsuspended
            if ($userRecord->suspended == true && $user->suspended == false) {
                $this->unsuspendUser($user);
            } // They got suspended
            else if ($userRecord->suspended == false && $user->suspended == true) {
                $this->suspendUser($user);
            }

            // They got activated
            if ($userRecord->pending == true && $user->pending == false) {
                $this->activateUser($user);
            }
        }

        return $success;
    }

    /**
     * Returns a user’s preferences.
     *
     * @param integer $userId The user’s ID
     *
     * @return array The user’s preferences
     */
    public function getUserPreferences($userId)
    {
        // TODO: Remove try/catch after next breakpoint
        try {
            $preferences = (new Query())
                ->select('preferences')
                ->from('{{%userpreferences}}')
                ->where(['userId' => $userId])
                ->scalar();

            return $preferences ? Json::decode($preferences) : [];
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Saves a user’s preferences.
     *
     * @param User  $user        The user
     * @param array $preferences The user’s new preferences
     */
    public function saveUserPreferences(User $user, $preferences)
    {
        $preferences = $user->mergePreferences($preferences);

        Craft::$app->getDb()->createCommand()
            ->insertOrUpdate(
                '{{%userpreferences}}',
                ['userId' => $user->id],
                ['preferences' => Json::encode($preferences)],
                false)
            ->execute();
    }

    /**
     * Sends a new account activation email for a user, regardless of their status.
     *
     * A new verification code will generated for the user overwriting any existing one.
     *
     * @param User $user The user to send the activation email to.
     *
     * @return boolean Whether the email was sent successfully.
     */
    public function sendActivationEmail(User $user)
    {
        // If the user doesn't have a password yet, use a Password Reset URL
        if (!$user->password) {
            $url = $this->getPasswordResetUrl($user);
        } else {
            $url = $this->getEmailVerifyUrl($user);
        }

        return Craft::$app->getMailer()
            ->composeFromKey('account_activation', ['link' => Template::getRaw($url)])
            ->setTo($user)
            ->send();
    }

    /**
     * Sends a new email verification email to a user, regardless of their status.
     *
     * A new verification code will generated for the user overwriting any existing one.
     *
     * @param User $user The user to send the activation email to.
     *
     * @return boolean Whether the email was sent successfully.
     */
    public function sendNewEmailVerifyEmail(User $user)
    {
        $url = $this->getEmailVerifyUrl($user);

        return Craft::$app->getMailer()
            ->composeFromKey('verify_new_email', ['link' => Template::getRaw($url)])
            ->setTo($user)
            ->send();
    }

    /**
     * Sends a password reset email to a user.
     *
     * A new verification code will generated for the user overwriting any existing one.
     *
     * @param User $user The user to send the forgot password email to.
     *
     * @return boolean Whether the email was sent successfully.
     */
    public function sendPasswordResetEmail(User $user)
    {
        $url = $this->getPasswordResetUrl($user);

        return Craft::$app->getMailer()
            ->composeFromKey('forgot_password', ['link' => Template::getRaw($url)])
            ->setTo($user)
            ->send();
    }

    /**
     * Sets a new verification code on a user, and returns their new Email Verification URL.
     *
     * @param User $user The user that should get the new Email Verification URL.
     *
     * @return string The new Email Verification URL.
     */
    public function getEmailVerifyUrl(User $user)
    {
        $userRecord = $this->_getUserRecordById($user->id);
        $unhashedVerificationCode = $this->_setVerificationCodeOnUserRecord($userRecord);
        $userRecord->save();

        if ($user->can('accessCp')) {
            $url = Url::getActionUrl('users/verifyemail',
                ['code' => $unhashedVerificationCode, 'id' => $user->uid],
                Craft::$app->getRequest()->getIsSecureConnection() ? 'https' : 'http');
        } else {
            // We want to hide the CP trigger if they don't have access to the CP.
            $path = Craft::$app->getConfig()->get('actionTrigger').'/users/verifyemail';
            $params = [
                'code' => $unhashedVerificationCode,
                'id' => $user->uid
            ];
            $protocol = Url::getProtocolForTokenizedUrl();

            // todo: should we factor in the user's preferred language (as we did in v2)?
            $siteId = Craft::$app->getSites()->getPrimarySite()->id;
            $url = Url::getSiteUrl($path, $params, $protocol, $siteId);
        }

        return $url;
    }

    /**
     * Sets a new verification code on a user, and returns their new Password Reset URL.
     *
     * @param User $user The user that should get the new Password Reset URL
     *
     * @return string The new Password Reset URL.
     */
    public function getPasswordResetUrl(User $user)
    {
        $userRecord = $this->_getUserRecordById($user->id);
        $unhashedVerificationCode = $this->_setVerificationCodeOnUserRecord($userRecord);
        $userRecord->save();

        $path = Craft::$app->getConfig()->get('actionTrigger').'/users/set-password';
        $params = [
            'code' => $unhashedVerificationCode,
            'id' => $user->uid
        ];
        $protocol = Url::getProtocolForTokenizedUrl();

        if ($user->can('accessCp')) {
            return Url::getCpUrl($path, $params, $protocol);
        }

        // todo: should we factor in the user's preferred language (as we did in v2)?
        $siteId = Craft::$app->getSites()->getPrimarySite()->id;

        return Url::getSiteUrl($path, $params, $protocol, $siteId);
    }

    /**
     * Crops and saves a user’s photo.
     *
     * @param User $user the user.
     * @param string $fileLocation the local image path on server
     * @param string $filename name of the file to use, defaults to filename of $imagePath
     *
     * @return boolean Whether the photo was saved successfully.
     * @throws ImageException if the file provided is not a manipulatable image
     * @throws VolumeException if the user photo Volume is not provided or is invalid
     */
    public function saveUserPhoto($fileLocation, User $user, $filename = "")
    {
        $filenameToUse = AssetsHelper::prepareAssetName($filename ?: Io::getFilename($fileLocation, false), true, true);

        if(!Image::isImageManipulatable(Io::getExtension($fileLocation))) {
            throw new ImageException(Craft::t('app', 'User photo must be an image that Craft can manipulate.'));
        }

        $volumes = Craft::$app->getVolumes();
        $volumeId = Craft::$app->getSystemSettings()->getSetting('users', 'photoVolumeId');

        if (!($volumeId && $volume = $volumes->getVolumeById($volumeId))) {
            throw new VolumeException(Craft::t('app',
                'The volume set for user photo storage is not valid.'));
        }

        $assets = Craft::$app->getAssets();

        // If the photo exists, just replace the file.
        if (!empty($user->photoId)) {
            // No longer a new file.
            $assets->replaceAssetFile($assets->getAssetById($user->photoId), $fileLocation, $filenameToUse);
        } else {
            $folderId = $volumes->ensureTopFolder($volumes->getVolumeById($volumeId));
            $filenameToUse = $assets->getNameReplacementInFolder($filenameToUse, $folderId);

            $photo = new Asset();
            $photo->title = StringHelper::toTitleCase(Io::getFilename($filenameToUse, false));
            $photo->newFilePath = $fileLocation;
            $photo->filename = $filenameToUse;
            $photo->folderId = $folderId;
            $photo->volumeId = $volumeId;

            // Save and delete the temporary file
            $assets->saveAsset($photo);

            $user->photoId = $photo->id;
            Craft::$app->getUsers()->saveUser($user);
        }

        return true;
    }

    /**
     * Deletes a user's photo.
     *
     * @param User $user The user.
     *
     * @return void
     */
    public function deleteUserPhoto(User $user)
    {
        Craft::$app->getAssets()->deleteAssetsByIds($user->photoId);
    }

    /**
     * Changes a user’s password.
     *
     * @param User    $user           The user.
     * @param boolean $forceDifferent Whether to force the new password to be different than any existing password.
     *
     * @return boolean Whether the user’s new password was saved successfully.
     */
    public function changePassword(User $user, $forceDifferent = false)
    {
        $userRecord = $this->_getUserRecordById($user->id);

        if ($this->_setPasswordOnUserRecord($user, $userRecord, true, $forceDifferent)) {
            $userRecord->save();

            return true;
        }

        return false;
    }

    /**
     * Updates a user's record for a successful login.
     *
     * @param User $user
     *
     * @return boolean
     */
    public function updateUserLoginInfo(User $user)
    {
        $userRecord = $this->_getUserRecordById($user->id);

        $userRecord->lastLoginDate = $user->lastLoginDate = DateTimeHelper::currentUTCDateTime();
        $userRecord->lastLoginAttemptIp = Craft::$app->getRequest()->getUserIP();
        $userRecord->invalidLoginWindowStart = null;
        $userRecord->invalidLoginCount = $user->invalidLoginCount = null;
        $userRecord->verificationCode = null;
        $userRecord->verificationCodeIssuedDate = null;

        return $userRecord->save();
    }

    /**
     * Handles an invalid login for a user.
     *
     * @param User $user The user.
     *
     * @return boolean Whether the user’s record was updated successfully.
     */
    public function handleInvalidLogin(User $user)
    {
        $userRecord = $this->_getUserRecordById($user->id);
        $currentTime = DateTimeHelper::currentUTCDateTime();

        $userRecord->lastInvalidLoginDate = $user->lastInvalidLoginDate = $currentTime;
        $userRecord->lastLoginAttemptIp = Craft::$app->getRequest()->getUserIP();

        $maxInvalidLogins = Craft::$app->getConfig()->get('maxInvalidLogins');

        if ($maxInvalidLogins) {
            if ($this->_isUserInsideInvalidLoginWindow($userRecord)) {
                $userRecord->invalidLoginCount++;

                // Was that one bad password too many?
                if ($userRecord->invalidLoginCount > $maxInvalidLogins) {
                    $userRecord->locked = true;
                    $user->locked = true;
                    $userRecord->invalidLoginCount = null;
                    $userRecord->invalidLoginWindowStart = null;
                    $userRecord->lockoutDate = $user->lockoutDate = $currentTime;
                }
            } else {
                // Start the invalid login window and counter
                $userRecord->invalidLoginWindowStart = $currentTime;
                $userRecord->invalidLoginCount = 1;
            }

            // Update the counter on the user model
            $user->invalidLoginCount = $userRecord->invalidLoginCount;
        }

        return $userRecord->save();
    }

    /**
     * Activates a user, bypassing email verification.
     *
     * @param User $user The user.
     *
     * @return boolean Whether the user was activated successfully.
     * @throws \Exception if reasons
     */
    public function activateUser(User $user)
    {
        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            // Fire a 'beforeActivateUser' event
            $event = new UserActivateEvent([
                'user' => $user,
            ]);

            $this->trigger(self::EVENT_BEFORE_ACTIVATE_USER, $event);

            // Is the event is giving us the go-ahead?
            if ($event->isValid) {
                $userRecord = $this->_getUserRecordById($user->id);

                $userRecord->setActive();
                $user->setActive();
                $userRecord->verificationCode = null;
                $userRecord->verificationCodeIssuedDate = null;
                $userRecord->save();

                // If they have an unverified email address, now is the time to set it to their primary email address
                $this->verifyEmailForUser($user);
                $success = true;
            } else {
                $success = false;
            }

            // Commit the transaction regardless of whether we activated the user, in case something changed
            // in onBeforeActivateUser
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();

            throw $e;
        }

        if ($success) {
            // Fire an 'afterActivateUser' event
            $this->trigger(self::EVENT_AFTER_ACTIVATE_USER, new UserActivateEvent([
                'user' => $user
            ]));
        }

        return $success;
    }

    /**
     * If 'unverifiedEmail' is set on the User, then this method will transfer it to the official email property
     * and clear the unverified one.
     *
     * @param User $user
     */
    public function verifyEmailForUser(User $user)
    {
        if ($user->unverifiedEmail) {
            $userRecord = $this->_getUserRecordById($user->id);
            $userRecord->email = $user->unverifiedEmail;

            if (Craft::$app->getConfig()->get('useEmailAsUsername')) {
                $userRecord->username = $user->unverifiedEmail;
            }

            $userRecord->unverifiedEmail = null;
            $userRecord->save();

            // If the user status is pending, let's activate them.
            if ($userRecord->pending == true) {
                $this->activateUser($user);
            }
        }
    }

    /**
     * Unlocks a user, bypassing the cooldown phase.
     *
     * @param User $user The user.
     *
     * @return boolean Whether the user was unlocked successfully.
     * @throws \Exception if reasons
     */
    public function unlockUser(User $user)
    {
        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            // Fire a 'beforeUnlockUser' event
            $event = new UserUnlockEvent([
                'user' => $user,
            ]);

            $this->trigger(self::EVENT_BEFORE_UNLOCK_USER, $event);

            // Is the event is giving us the go-ahead?
            if ($event->isValid) {
                $userRecord = $this->_getUserRecordById($user->id);

                $userRecord->locked = false;
                $user->locked = false;

                $userRecord->invalidLoginCount = $user->invalidLoginCount = null;
                $userRecord->invalidLoginWindowStart = null;
                $userRecord->lockoutDate = $user->lockoutDate = null;

                $userRecord->save();
                $success = true;
            } else {
                $success = false;
            }

            // Commit the transaction regardless of whether we unlocked the user, in case something changed
            // in onBeforeUnlockUser
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();

            throw $e;
        }

        if ($success) {
            // Fire an 'afterUnlockUser' event
            $this->trigger(self::EVENT_AFTER_UNLOCK_USER, new UserUnlockEvent([
                'user' => $user
            ]));
        }

        return $success;
    }

    /**
     * Suspends a user.
     *
     * @param User $user The user.
     *
     * @return boolean Whether the user was suspended successfully.
     * @throws \Exception if reasons
     */
    public function suspendUser(User $user)
    {
        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            // Fire a 'beforeSuspendUser' event
            $event = new UserSuspendEvent([
                'user' => $user,
            ]);

            $this->trigger(self::EVENT_BEFORE_SUSPEND_USER, $event);

            // Is the event is giving us the go-ahead?
            if ($event->isValid) {
                $userRecord = $this->_getUserRecordById($user->id);

                $userRecord->suspended = true;
                $user->suspended = true;

                $userRecord->save();
                $success = true;
            } else {
                $success = false;
            }

            // Commit the transaction regardless of whether we saved the user, in case something changed
            // in onBeforeSuspendUser
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();

            throw $e;
        }

        if ($success) {
            // Fire an 'afterSuspendUser' event
            $this->trigger(self::EVENT_AFTER_SUSPEND_USER, new UserSuspendEvent([
                'user' => $user
            ]));
        }

        return $success;
    }

    /**
     * Unsuspends a user.
     *
     * @param User $user The user.
     *
     * @return boolean Whether the user was unsuspended successfully.
     * @throws \Exception if reasons
     */
    public function unsuspendUser(User $user)
    {
        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            // Fire a 'beforeUnsuspendUser' event
            $event = new UserUnsuspendEvent([
                'user' => $user,
            ]);

            $this->trigger(self::EVENT_BEFORE_UNSUSPEND_USER, $event);

            // Is the event is giving us the go-ahead?
            if ($event->isValid) {
                $userRecord = $this->_getUserRecordById($user->id);

                $userRecord->suspended = false;
                $user->suspended = false;

                $userRecord->save();
                $success = true;
            } else {
                $success = false;
            }

            // Commit the transaction regardless of whether we unsuspended the user, in case something changed
            // in onBeforeUnsuspendUser
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();

            throw $e;
        }

        if ($success) {
            // Fire an 'afterUnsuspendUser' event
            $this->trigger(self::EVENT_AFTER_UNSUSPEND_USER, new UserUnsuspendEvent([
                'user' => $user
            ]));
        }

        return $success;
    }

    /**
     * Deletes a user.
     *
     * @param User      $user              The user to be deleted.
     * @param User|null $transferContentTo The user who should take over the deleted user’s content.
     *
     * @return boolean Whether the user was deleted successfully.
     * @throws \Exception if reasons
     */
    public function deleteUser(User $user, User $transferContentTo = null)
    {
        if (!$user->id) {
            return false;
        }

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            // Fire a 'beforeDeleteUser' event
            $event = new DeleteUserEvent([
                'user' => $user,
                'transferContentTo' => $transferContentTo
            ]);

            $this->trigger(self::EVENT_BEFORE_DELETE_USER, $event);

            // Is the event is giving us the go-ahead?
            if ($event->isValid) {
                // Get the entry IDs that belong to this user
                $entryIds = (new Query())
                    ->select('id')
                    ->from('{{%entries}}')
                    ->where(['authorId' => $user->id])
                    ->column();

                // Should we transfer the content to a new user?
                if ($transferContentTo) {
                    // Delete the template caches for any entries authored by this user
                    Craft::$app->getTemplateCaches()->deleteCachesByElementId($entryIds);

                    // Update the entry/version/draft tables to point to the new user
                    $userRefs = [
                        '{{%entries}}' => 'authorId',
                        '{{%entrydrafts}}' => 'creatorId',
                        '{{%entryversions}}' => 'creatorId',
                    ];

                    foreach ($userRefs as $table => $column) {
                        Craft::$app->getDb()->createCommand()
                            ->update(
                                $table,
                                [
                                    $column => $transferContentTo->id
                                ],
                                [
                                    $column => $user->id
                                ])
                            ->execute();
                    }
                } else {
                    // Delete the entries
                    Craft::$app->getElements()->deleteElementById($entryIds);
                }

                // Delete the user
                $success = Craft::$app->getElements()->deleteElementById($user->id);

                // If it didn't work, rollback the transaction in case something changed in onBeforeDeleteUser
                if (!$success) {
                    $transaction->rollBack();

                    return false;
                }
            } else {
                $success = false;
            }

            // Commit the transaction regardless of whether we deleted the user,
            // in case something changed in onBeforeDeleteUser
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();

            throw $e;
        }

        if ($success) {
            // Fire an 'afterDeleteUser' event
            $this->trigger(self::EVENT_AFTER_DELETE_USER,
                new DeleteUserEvent([
                    'user' => $user,
                    'transferContentTo' => $transferContentTo
                ]));
        }

        return $success;
    }

    /**
     * Shuns a message for a user.
     *
     * @param integer  $userId     The user’s ID.
     * @param string   $message    The message to be shunned.
     * @param DateTime $expiryDate When the message should be un-shunned. Defaults to `null` (never un-shun).
     *
     * @return boolean Whether the message was shunned successfully.
     */
    public function shunMessageForUser($userId, $message, $expiryDate = null)
    {
        $affectedRows = Craft::$app->getDb()->createCommand()
            ->insertOrUpdate(
                '{{%shunnedmessages}}',
                [
                    'userId' => $userId,
                    'message' => $message
                ],
                [
                    'expiryDate' => Db::prepareDateForDb($expiryDate)
                ])
            ->execute();

        return (bool)$affectedRows;
    }

    /**
     * Un-shuns a message for a user.
     *
     * @param integer $userId  The user’s ID.
     * @param string  $message The message to un-shun.
     *
     * @return boolean Whether the message was un-shunned successfully.
     */
    public function unshunMessageForUser($userId, $message)
    {
        $affectedRows = Craft::$app->getDb()->createCommand()
            ->delete(
                '{{%shunnedmessages}}',
                [
                    'userId' => $userId,
                    'message' => $message
                ])
            ->execute();

        return (bool)$affectedRows;
    }

    /**
     * Returns whether a message is shunned for a user.
     *
     * @param integer $userId  The user’s ID.
     * @param string  $message The message to check.
     *
     * @return boolean Whether the user has shunned the message.
     */
    public function hasUserShunnedMessage($userId, $message)
    {
        return (new Query())
            ->from('{{%shunnedmessages}}')
            ->where([
                'and',
                'userId = :userId',
                'message = :message',
                ['or', 'expiryDate IS NULL', 'expiryDate > :now']
            ], [
                ':userId' => $userId,
                ':message' => $message,
                ':now' => Db::prepareDateForDb(new \DateTime())
            ])
            ->exists();
    }

    /**
     * Sets a new verification code on the user's record.
     *
     * @param User $user The user.
     *
     * @return string The user’s brand new verification code.
     */
    public function setVerificationCodeOnUser(User $user)
    {
        $userRecord = $this->_getUserRecordById($user->id);
        $unhashedVerificationCode = $this->_setVerificationCodeOnUserRecord($userRecord);
        $userRecord->save();

        return $unhashedVerificationCode;
    }

    /**
     * Deletes any pending users that have shown zero sense of urgency and are just taking up space.
     *
     * This method will check the
     * [purgePendingUsersDuration](http://craftcms.com/docs/config-settings#purgePendingUsersDuration) config
     * setting, and if it is set to a valid duration, it will delete any user accounts that were created that duration
     * ago, and have still not activated their account.
     *
     * @return void
     */
    public function purgeExpiredPendingUsers()
    {
        if (($duration = Craft::$app->getConfig()->get('purgePendingUsersDuration')) !== false) {
            $interval = new DateInterval($duration);
            $expire = DateTimeHelper::currentUTCDateTime();
            $pastTime = $expire->sub($interval);

            $userIds = (new Query())
                ->select('id')
                ->from('{{%users}}')
                ->where([
                    'and',
                    'pending=1',
                    'verificationCodeIssuedDate < :pastTime'
                ], [':pastTime' => Db::prepareDateForDb($pastTime)])
                ->column();

            if ($userIds) {
                foreach ($userIds as $userId) {
                    $user = $this->getUserById($userId);
                    $this->deleteUser($user);

                    Craft::info('Just deleted pending userId '.$userId.' ('.$user->username.'), because the were more than '.$duration.' old', __METHOD__);
                }
            }
        }
    }

    /**
     * Assigns a user to a given list of user groups.
     *
     * @param integer       $userId   The user’s ID.
     * @param integer|array $groupIds The groups’ IDs.
     *
     * @return boolean Whether the users were successfully assigned to the groups.
     */
    public function assignUserToGroups($userId, $groupIds = null)
    {
        // Make sure $groupIds is an array
        if (!is_array($groupIds)) {
            $groupIds = $groupIds ? [$groupIds] : [];
        }

        // Fire a 'beforeAssignUserToGroups' event
        $event = new UserGroupsAssignEvent([
            'userId' => $userId,
            'groupIds' => $groupIds
        ]);

        $this->trigger(self::EVENT_BEFORE_ASSIGN_USER_TO_GROUPS, $event);

        if ($event->isValid) {
            // Delete their existing groups
            Craft::$app->getDb()->createCommand()
                ->delete('{{%usergroups_users}}', ['userId' => $userId])
                ->execute();

            if ($groupIds) {
                // Add the new ones
                $values = [];
                foreach ($groupIds as $groupId) {
                    $values[] = [$groupId, $userId];
                }

                Craft::$app->getDb()->createCommand()
                    ->batchInsert(
                        '{{%usergroups_users}}',
                        [
                            'groupId',
                            'userId'
                        ],
                        $values)
                    ->execute();
            }

            // Fire an 'afterAssignUserToGroups' event
            $this->trigger(self::EVENT_AFTER_ASSIGN_USER_TO_GROUPS, new UserGroupsAssignEvent([
                'userId' => $userId,
                'groupIds' => $groupIds
            ]));

            // Need to invalidate the User element's cached values.
            $user = $this->getUserById($userId);
            $userGroups = [];

            foreach ($groupIds as $groupId) {
                $userGroup = Craft::$app->getUserGroups()->getGroupById($groupId);

                if ($userGroup) {
                    $userGroups[] = $userGroup;
                }
            }

            $user->setGroups($userGroups);
            return true;
        }

        return false;
    }

    /**
     * Assigns a user to the default user group.
     *
     * This method is called toward the end of a public registration request.
     *
     * @param User $user The user that was just registered.
     *
     * @return boolean Whether the user was assigned to the default group.
     */
    public function assignUserToDefaultGroup(User $user)
    {
        $defaultGroupId = Craft::$app->getSystemSettings()->getSetting('users', 'defaultGroup');

        if ($defaultGroupId) {
            // Fire a 'beforeAssignUserToDefaultGroup' event
            $event = new UserAssignGroupEvent([
                'user' => $user
            ]);

            $this->trigger(self::EVENT_BEFORE_ASSIGN_USER_TO_DEFAULT_GROUP, $event);

            // Is the event is giving us the go-ahead?
            if ($event->isValid) {
                $success = $this->assignUserToGroups($user->id, [$defaultGroupId]);

                if ($success) {
                    // Fire an 'afterAssignUserToDefaultGroup' event
                    $this->trigger(self::EVENT_AFTER_ASSIGN_USER_TO_DEFAULT_GROUP,
                        new UserAssignGroupEvent([
                            'user' => $user
                        ]));

                    return true;
                }
            }
        }

        return false;
    }

    // Private Methods
    // =========================================================================

    /**
     * Gets a user record by its ID.
     *
     * @param integer $userId
     *
     * @return UserRecord
     * @throws UserNotFoundException if $userId is invalid
     */
    private function _getUserRecordById($userId)
    {
        $userRecord = UserRecord::findOne($userId);

        if (!$userRecord) {
            throw new UserNotFoundException("No user exists with the ID '{$userId}'");
        }

        return $userRecord;
    }

    /**
     * Sets a user record up for a new verification code without saving it.
     *
     * @param  UserRecord $userRecord
     *
     * @return string
     */
    private function _setVerificationCodeOnUserRecord(UserRecord $userRecord)
    {
        $securityService = Craft::$app->getSecurity();
        $unhashedCode = $securityService->generateRandomString(32);
        $hashedCode = $securityService->hashPassword($unhashedCode);
        $userRecord->verificationCode = $hashedCode;
        $userRecord->verificationCodeIssuedDate = DateTimeHelper::currentUTCDateTime();

        return $unhashedCode;
    }

    /**
     * Determines if a user is within their invalid login window.
     *
     * @param UserRecord $userRecord
     *
     * @return boolean
     */
    private function _isUserInsideInvalidLoginWindow(UserRecord $userRecord)
    {
        if ($userRecord->invalidLoginWindowStart) {
            $duration = new DateInterval(Craft::$app->getConfig()->get('invalidLoginWindowDuration'));
            $invalidLoginWindowStart = DateTimeHelper::toDateTime($userRecord->invalidLoginWindowStart);
            $end = $invalidLoginWindowStart->add($duration);

            return ($end >= DateTimeHelper::currentUTCDateTime());
        }

        return false;
    }

    /**
     * Sets a user record up for a new password without saving it.
     *
     * @param User       $user                        The user who is getting a new password.
     * @param UserRecord $userRecord                  The user’s record.
     * @param boolean    $updatePasswordResetRequired Whether the user’s
     *                                                [[User::passwordResetRequired passwordResetRequired]]
     *                                                attribute should be set `false`. Default is `true`.
     * @param boolean    $forceDifferentPassword      Whether to force a new password to be different from any existing
     *                                                password.
     *
     * @return boolean
     */
    private function _setPasswordOnUserRecord(User $user, UserRecord $userRecord, $updatePasswordResetRequired = true, $forceDifferentPassword = false)
    {
        $isNewUser = !$user->id;
        $validates = true;

        // Validate the password first
        $passwordModel = new Password();
        $passwordModel->password = $user->newPassword;

        // If it's a new user AND we allow public registration, set it on the 'password' field and not 'newpassword'.
        if ($isNewUser && Craft::$app->getSystemSettings()->getSetting('users', 'allowPublicRegistration')) {
            $passwordErrorField = 'password';
        } else {
            $passwordErrorField = 'newPassword';
        }

        if ($passwordModel->validate()) {
            if ($forceDifferentPassword) {
                // See if the passwords are the same.
                if (Craft::$app->getSecurity()->validatePassword($user->newPassword, $userRecord->password)) {
                    $user->addErrors([
                        $passwordErrorField => Craft::t('app', 'That password is the same as your old password. Please choose a new one.'),
                    ]);

                    $validates = false;
                }
            }

            if ($validates) {
                // Fire a 'beforeSetPassword' event
                $event = new UserEvent([
                    'user' => $user,
                    'isNew' => $isNewUser,
                ]);

                $this->trigger(self::EVENT_BEFORE_SET_PASSWORD, $event);

                // Is the event is giving us the go-ahead?
                $validates = $event->isValid;
            }
        }

        if ($validates) {
            $hash = Craft::$app->getSecurity()->hashPassword($user->newPassword);

            $userRecord->password = $user->password = $hash;
            $userRecord->invalidLoginWindowStart = null;
            $userRecord->invalidLoginCount = $user->invalidLoginCount = null;
            $userRecord->verificationCode = null;
            $userRecord->verificationCodeIssuedDate = null;

            // If it's an existing user, reset the passwordResetRequired bit.
            if ($updatePasswordResetRequired && $user->id) {
                $userRecord->passwordResetRequired = $user->passwordResetRequired = false;
            }

            $userRecord->lastPasswordChangeDate = $user->lastPasswordChangeDate = DateTimeHelper::currentUTCDateTime();

            $user->newPassword = null;

            $success = true;
        } else {
            $user->addErrors([
                $passwordErrorField => $passwordModel->getErrors('password')
            ]);

            $success = false;
        }

        if ($success) {
            // Fire an 'afterSetPassword' event
            $this->trigger(self::EVENT_AFTER_SET_PASSWORD, new UserEvent([
                'user' => $user,
                'isNew' => $isNewUser
            ]));
        }

        return $success;
    }
}
