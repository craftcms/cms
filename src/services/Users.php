<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\services;

use Craft;
use craft\base\Volume;
use craft\db\Query;
use craft\elements\Asset;
use craft\elements\User;
use craft\errors\ImageException;
use craft\errors\UserNotFoundException;
use craft\errors\VolumeException;
use craft\events\UserActivateEvent;
use craft\events\UserAssignGroupEvent;
use craft\events\UserGroupsAssignEvent;
use craft\events\UserSuspendEvent;
use craft\events\UserUnlockEvent;
use craft\events\UserUnsuspendEvent;
use craft\helpers\Assets as AssetsHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\Image;
use craft\helpers\Json;
use craft\helpers\Template;
use craft\helpers\UrlHelper;
use craft\records\User as UserRecord;
use DateTime;
use yii\base\Component;
use yii\base\InvalidParamException;
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
     * @param int $userId The user’s ID.
     *
     * @return User|null The user with the given ID, or `null` if a user could not be found.
     */
    public function getUserById(int $userId)
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
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
    public function getUserByUsernameOrEmail(string $usernameOrEmail)
    {
        return User::find()
            ->where([
                'or',
                ['username' => $usernameOrEmail],
                ['email' => $usernameOrEmail]
            ])
            ->addSelect(['users.password', 'users.passwordResetRequired'])
            ->status(null)
            ->one();
    }

    /**
     * Returns a user by their UID.
     *
     * ```php
     * $user = Craft::$app->getUsers()->getUserByUid($userUid);
     * ```
     *
     * @param string $uid The user’s UID.
     *
     * @return User|null The user with the given UID, or `null` if a user could not be found.
     */
    public function getUserByUid(string $uid)
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
     * @return bool Whether the code is still valid.
     */
    public function isVerificationCodeValidForUser(User $user, string $code): bool
    {
        $valid = false;
        $userRecord = $this->_getUserRecordById($user->id);

        if ($userRecord) {
            $minCodeIssueDate = DateTimeHelper::currentUTCDateTime();
            $generalConfig = Craft::$app->getConfig()->getGeneral();
            $interval = DateTimeHelper::secondsToInterval($generalConfig->verificationCodeDuration);
            $minCodeIssueDate->sub($interval);
            $verificationCodeIssuedDate = new \DateTime($userRecord->verificationCodeIssuedDate, new \DateTimeZone('UTC'));

            $valid = $verificationCodeIssuedDate > $minCodeIssueDate;

            if (!$valid) {
                // It's expired, go ahead and remove it from the record so if they click the link again, it'll throw an
                // Exception.
                $userRecord = $this->_getUserRecordById($user->id);
                $userRecord->verificationCodeIssuedDate = null;
                $userRecord->verificationCode = null;
                $userRecord->save();
            } else {
                try {
                    $valid = Craft::$app->getSecurity()->validatePassword($code, $userRecord->verificationCode);
                } catch (InvalidParamException $e) {
                    $valid = false;
                }

                if (!$valid) {
                    Craft::warning('The verification code ('.$code.') given for userId: '.$user->id.' does not match the hash in the database.', __METHOD__);
                }
            }
        } else {
            Craft::warning('Could not find a user with id:'.$user->id.'.', __METHOD__);
        }

        return $valid;
    }

    /**
     * Returns a user’s preferences.
     *
     * @param int|null $userId The user’s ID
     *
     * @return array The user’s preferences
     */
    public function getUserPreferences(int $userId = null): array
    {
        // TODO: Remove try/catch after next breakpoint
        try {
            $preferences = (new Query())
                ->select(['preferences'])
                ->from(['{{%userpreferences}}'])
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
    public function saveUserPreferences(User $user, array $preferences)
    {
        $preferences = $user->mergePreferences($preferences);

        Craft::$app->getDb()->createCommand()
            ->upsert(
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
     * @return bool Whether the email was sent successfully.
     */
    public function sendActivationEmail(User $user): bool
    {
        // If the user doesn't have a password yet, use a Password Reset URL
        if (!$user->password) {
            $url = $this->getPasswordResetUrl($user);
        } else {
            $url = $this->getEmailVerifyUrl($user);
        }

        return Craft::$app->getMailer()
            ->composeFromKey('account_activation', ['link' => Template::raw($url)])
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
     * @return bool Whether the email was sent successfully.
     */
    public function sendNewEmailVerifyEmail(User $user): bool
    {
        $url = $this->getEmailVerifyUrl($user);

        return Craft::$app->getMailer()
            ->composeFromKey('verify_new_email', ['link' => Template::raw($url)])
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
     * @return bool Whether the email was sent successfully.
     */
    public function sendPasswordResetEmail(User $user): bool
    {
        $url = $this->getPasswordResetUrl($user);

        return Craft::$app->getMailer()
            ->composeFromKey('forgot_password', ['link' => Template::raw($url)])
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
    public function getEmailVerifyUrl(User $user): string
    {
        return $this->_getUserUrl($user, 'verify-email');
    }

    /**
     * Sets a new verification code on a user, and returns their new Password Reset URL.
     *
     * @param User $user The user that should get the new Password Reset URL
     *
     * @return string The new Password Reset URL.
     */
    public function getPasswordResetUrl(User $user): string
    {
        return $this->_getUserUrl($user, 'set-password');
    }

    /**
     * Crops and saves a user’s photo.
     *
     * @param User   $user         the user.
     * @param string $fileLocation the local image path on server
     * @param string $filename     name of the file to use, defaults to filename of $imagePath
     *
     * @return bool Whether the photo was saved successfully.
     * @throws ImageException if the file provided is not a manipulatable image
     * @throws VolumeException if the user photo Volume is not provided or is invalid
     */
    public function saveUserPhoto(string $fileLocation, User $user, string $filename = ''): bool
    {
        $filenameToUse = AssetsHelper::prepareAssetName($filename ?: pathinfo($fileLocation, PATHINFO_FILENAME), true, true);

        if (!Image::canManipulateAsImage(pathinfo($fileLocation, PATHINFO_EXTENSION))) {
            throw new ImageException(Craft::t('app', 'User photo must be an image that Craft can manipulate.'));
        }

        $volumes = Craft::$app->getVolumes();
        $volumeId = Craft::$app->getSystemSettings()->getSetting('users', 'photoVolumeId');

        /** @var Volume $volume */
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
            $folderId = $volumes->ensureTopFolder($volume);
            $filenameToUse = $assets->getNameReplacementInFolder($filenameToUse, $folderId);

            $photo = new Asset();
            $photo->setScenario(Asset::SCENARIO_CREATE);
            $photo->tempFilePath = $fileLocation;
            $photo->filename = $filenameToUse;
            $photo->newFolderId = $folderId;
            $photo->volumeId = $volumeId;
            $photo->fieldLayoutId = $volume->fieldLayoutId;

            // Save photo.
            $elementsService = Craft::$app->getElements();
            $elementsService->saveElement($photo);

            $user->photoId = $photo->id;
            $elementsService->saveElement($user, false);
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
        Craft::$app->getElements()->deleteElementById($user->photoId, Asset::class);
    }

    /**
     * Updates a user's record for a successful login.
     *
     * @param User $user
     *
     * @return bool
     */
    public function updateUserLoginInfo(User $user): bool
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
     * @return bool Whether the user’s record was updated successfully.
     */
    public function handleInvalidLogin(User $user): bool
    {
        $userRecord = $this->_getUserRecordById($user->id);
        $currentTime = DateTimeHelper::currentUTCDateTime();

        $userRecord->lastInvalidLoginDate = $user->lastInvalidLoginDate = $currentTime;
        $userRecord->lastLoginAttemptIp = Craft::$app->getRequest()->getUserIP();

        $maxInvalidLogins = Craft::$app->getConfig()->getGeneral()->maxInvalidLogins;

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
     * @return bool Whether the user was activated successfully.
     * @throws \Exception if reasons
     */
    public function activateUser(User $user): bool
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

            if (Craft::$app->getConfig()->getGeneral()->useEmailAsUsername) {
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
     * @return bool Whether the user was unlocked successfully.
     * @throws \Exception if reasons
     */
    public function unlockUser(User $user): bool
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
     * @return bool Whether the user was suspended successfully.
     * @throws \Exception if reasons
     */
    public function suspendUser(User $user): bool
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
     * @return bool Whether the user was unsuspended successfully.
     * @throws \Exception if reasons
     */
    public function unsuspendUser(User $user): bool
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
     * Shuns a message for a user.
     *
     * @param int           $userId     The user’s ID.
     * @param string        $message    The message to be shunned.
     * @param DateTime|null $expiryDate When the message should be un-shunned. Defaults to `null` (never un-shun).
     *
     * @return bool Whether the message was shunned successfully.
     */
    public function shunMessageForUser(int $userId, string $message, DateTime $expiryDate = null): bool
    {
        $affectedRows = Craft::$app->getDb()->createCommand()
            ->upsert(
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
     * @param int    $userId  The user’s ID.
     * @param string $message The message to un-shun.
     *
     * @return bool Whether the message was un-shunned successfully.
     */
    public function unshunMessageForUser(int $userId, string $message): bool
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
     * @param int    $userId  The user’s ID.
     * @param string $message The message to check.
     *
     * @return bool Whether the user has shunned the message.
     */
    public function hasUserShunnedMessage(int $userId, string $message): bool
    {
        return (new Query())
            ->from(['{{%shunnedmessages}}'])
            ->where([
                'and',
                [
                    'userId' => $userId,
                    'message' => $message
                ],
                [
                    'or',
                    ['expiryDate' => null],
                    ['>', 'expiryDate', Db::prepareDateForDb(new \DateTime())]
                ]
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
    public function setVerificationCodeOnUser(User $user): string
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
        $generalConfig = Craft::$app->getConfig()->getGeneral();

        if ($generalConfig->purgePendingUsersDuration !== 0) {
            $interval = DateTimeHelper::secondsToInterval($generalConfig->purgePendingUsersDuration);
            $expire = DateTimeHelper::currentUTCDateTime();
            $pastTime = $expire->sub($interval);

            $userIds = (new Query())
                ->select(['id'])
                ->from(['{{%users}}'])
                ->where([
                    'and',
                    ['pending' => '1'],
                    ['<', 'verificationCodeIssuedDate', Db::prepareDateForDb($pastTime)]
                ])
                ->column();

            if (!empty($userIds)) {
                foreach ($userIds as $userId) {
                    $user = $this->getUserById($userId);
                    Craft::$app->getElements()->deleteElement($user);
                    Craft::info("Just deleted pending user {$user->username} ({$userId}), because they took too long to activate their account.", __METHOD__);
                }
            }
        }
    }

    /**
     * Assigns a user to a given list of user groups.
     *
     * @param int            $userId   The user’s ID.
     * @param int|array|null $groupIds The groups’ IDs.
     *
     * @return bool Whether the users were successfully assigned to the groups.
     */
    public function assignUserToGroups(int $userId, $groupIds = null): bool
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

            if (!empty($groupIds)) {
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
     * @return bool Whether the user was assigned to the default group.
     */
    public function assignUserToDefaultGroup(User $user): bool
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
     * @param int $userId
     *
     * @return UserRecord
     * @throws UserNotFoundException if $userId is invalid
     */
    private function _getUserRecordById(int $userId): UserRecord
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
    private function _setVerificationCodeOnUserRecord(UserRecord $userRecord): string
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
     * @return bool
     */
    private function _isUserInsideInvalidLoginWindow(UserRecord $userRecord): bool
    {
        if ($userRecord->invalidLoginWindowStart) {
            $generalConfig = Craft::$app->getConfig()->getGeneral();
            $interval = DateTimeHelper::secondsToInterval($generalConfig->invalidLoginWindowDuration);
            $invalidLoginWindowStart = DateTimeHelper::toDateTime($userRecord->invalidLoginWindowStart);
            $end = $invalidLoginWindowStart->add($interval);

            return ($end >= DateTimeHelper::currentUTCDateTime());
        }

        return false;
    }

    /**
     * Sets a new verification code on a user, and returns their new verification URL
     *
     * @param User   $user   The user that should get the new Password Reset URL
     * @param string $action The UsersController action that the URL should point to
     *
     * @return string The new Password Reset URL.
     * @see getPasswordResetUrl()
     * @see getEmailVerifyUrl()
     */
    private function _getUserUrl(User $user, string $action): string
    {
        $userRecord = $this->_getUserRecordById($user->id);
        $unhashedVerificationCode = $this->_setVerificationCodeOnUserRecord($userRecord);
        $userRecord->save();

        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $path = $generalConfig->actionTrigger.'/users/'.$action;
        $params = [
            'code' => $unhashedVerificationCode,
            'id' => $user->uid
        ];

        $protocol = UrlHelper::getProtocolForTokenizedUrl();

        if ($user->can('accessCp')) {
            // Only use getCpUrl() if the base CP URL has been explicitly set,
            // so UrlHelper won't use HTTP_HOST
            if ($generalConfig->baseCpUrl) {
                return UrlHelper::cpUrl($path, $params, $protocol);
            }

            $path = $generalConfig->cpTrigger.'/'.$path;
        }

        // todo: should we factor in the user's preferred language (as we did in v2)?
        $siteId = Craft::$app->getSites()->getPrimarySite()->id;
        return UrlHelper::siteUrl($path, $params, $protocol, $siteId);
    }
}
