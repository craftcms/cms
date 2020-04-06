<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\base\Field;
use craft\base\Volume;
use craft\db\Query;
use craft\db\Table;
use craft\elements\Asset;
use craft\elements\User;
use craft\errors\ImageException;
use craft\errors\InvalidSubpathException;
use craft\errors\UserNotFoundException;
use craft\errors\VolumeException;
use craft\events\ConfigEvent;
use craft\events\FieldEvent;
use craft\events\UserAssignGroupEvent;
use craft\events\UserEvent;
use craft\events\UserGroupsAssignEvent;
use craft\helpers\Assets as AssetsHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\Image;
use craft\helpers\Json;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\helpers\StringHelper;
use craft\helpers\Template;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use craft\records\User as UserRecord;
use craft\web\Request;
use DateTime;
use yii\base\Component;
use yii\base\InvalidArgumentException;
use yii\db\Exception as DbException;

/**
 * The Users service provides APIs for managing users.
 * An instance of the Users service is globally accessible in Craft via [[\craft\base\ApplicationTrait::getUsers()|`Craft::$app->users`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Users extends Component
{
    /**
     * @event UserEvent The event that is triggered before a user's email is verified.
     */
    const EVENT_BEFORE_VERIFY_EMAIL = 'beforeVerifyEmail';

    /**
     * @event UserEvent The event that is triggered after a user's email is verified.
     */
    const EVENT_AFTER_VERIFY_EMAIL = 'afterVerifyEmail';

    /**
     * @event UserEvent The event that is triggered before a user is activated.
     *
     * You may set [[UserEvent::isValid]] to `false` to prevent the user from getting activated.
     */
    const EVENT_BEFORE_ACTIVATE_USER = 'beforeActivateUser';

    /**
     * @event UserEvent The event that is triggered after a user is activated.
     */
    const EVENT_AFTER_ACTIVATE_USER = 'afterActivateUser';

    /**
     * @event UserEvent The event that is triggered after a user is locked.
     */
    const EVENT_AFTER_LOCK_USER = 'afterLockUser';

    /**
     * @event UserEvent The event that is triggered before a user is unlocked.
     *
     * You may set [[UserEvent::isValid]] to `false` to prevent the user from getting unlocked.
     */
    const EVENT_BEFORE_UNLOCK_USER = 'beforeUnlockUser';

    /**
     * @event UserEvent The event that is triggered after a user is unlocked.
     */
    const EVENT_AFTER_UNLOCK_USER = 'afterUnlockUser';

    /**
     * @event UserEvent The event that is triggered before a user is suspended.
     *
     * You may set [[UserEvent::isValid]] to `false` to prevent the user from getting suspended.
     */
    const EVENT_BEFORE_SUSPEND_USER = 'beforeSuspendUser';

    /**
     * @event UserEvent The event that is triggered after a user is suspended.
     */
    const EVENT_AFTER_SUSPEND_USER = 'afterSuspendUser';

    /**
     * @event UserEvent The event that is triggered before a user is unsuspended.
     *
     * You may set [[UserEvent::isValid]] to `false` to prevent the user from getting unsuspended.
     */
    const EVENT_BEFORE_UNSUSPEND_USER = 'beforeUnsuspendUser';

    /**
     * @event UserEvent The event that is triggered after a user is unsuspended.
     */
    const EVENT_AFTER_UNSUSPEND_USER = 'afterUnsuspendUser';

    /**
     * @event UserGroupsAssignEvent The event that is triggered before a user is assigned to some user groups.
     *
     * You may set [[UserGroupsAssignEvent::isValid]] to `false` to prevent the user from getting assigned to the groups.
     */
    const EVENT_BEFORE_ASSIGN_USER_TO_GROUPS = 'beforeAssignUserToGroups';

    /**
     * @event UserGroupsAssignEvent The event that is triggered after a user is assigned to some user groups.
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

    const CONFIG_USERLAYOUT_KEY = 'users.fieldLayouts';

    /**
     * Returns a user by their ID.
     *
     * ```php
     * $user = Craft::$app->users->getUserById($userId);
     * ```
     *
     * @param int $userId The user’s ID.
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
     * $user = Craft::$app->users->getUserByUsernameOrEmail($loginName);
     * ```
     *
     * @param string $usernameOrEmail The user’s username or email.
     * @return User|null The user with the given username/email, or `null` if a user could not be found.
     */
    public function getUserByUsernameOrEmail(string $usernameOrEmail)
    {
        $query = User::find()
            ->addSelect(['users.password', 'users.passwordResetRequired'])
            ->anyStatus();

        if (Craft::$app->getDb()->getIsMysql()) {
            $query
                ->where([
                    'username' => $usernameOrEmail,
                ])
                ->orWhere([
                    'email' => $usernameOrEmail,
                ]);
        } else {
            // Postgres is case-sensitive
            $query
                ->where([
                    'lower([[username]])' => mb_strtolower($usernameOrEmail),
                ])
                ->orWhere([
                    'lower([[email]])' => mb_strtolower($usernameOrEmail),
                ]);
        }

        return $query->one();
    }

    /**
     * Returns a user by their UID.
     *
     * ```php
     * $user = Craft::$app->users->getUserByUid($userUid);
     * ```
     *
     * @param string $uid The user’s UID.
     * @return User|null The user with the given UID, or `null` if a user could not be found.
     */
    public function getUserByUid(string $uid)
    {
        return User::find()
            ->uid($uid)
            ->anyStatus()
            ->one();
    }

    /**
     * Returns whether a verification code is valid for the given user.
     *
     * This method first checks if the code has expired past the
     * <config:verificationCodeDuration> config setting. If it is still valid,
     * then, the checks the validity of the contents of the code.
     *
     * @param User $user The user to check the code for.
     * @param string $code The verification code to check for.
     * @return bool Whether the code is still valid.
     */
    public function isVerificationCodeValidForUser(User $user, string $code): bool
    {
        $userRecord = $this->_getUserRecordById($user->id);
        $minCodeIssueDate = DateTimeHelper::currentUTCDateTime();
        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $interval = DateTimeHelper::secondsToInterval($generalConfig->verificationCodeDuration);
        $minCodeIssueDate->sub($interval);
        $verificationCodeIssuedDate = new \DateTime($userRecord->verificationCodeIssuedDate, new \DateTimeZone('UTC'));

        // Make sure it's not expired
        if ($verificationCodeIssuedDate < $minCodeIssueDate) {
            // Remove it from the record so if they click the link again, it'll throw an exception
            $userRecord = $this->_getUserRecordById($user->id);
            $userRecord->verificationCodeIssuedDate = null;
            $userRecord->verificationCode = null;
            $userRecord->save();

            Craft::warning('The verification code (' . $code . ') given for userId: ' . $user->id . ' is expired.', __METHOD__);
            return false;
        }

        try {
            $valid = Craft::$app->getSecurity()->validatePassword($code, $userRecord->verificationCode);
        } catch (InvalidArgumentException $e) {
            $valid = false;
        }

        if (!$valid) {
            Craft::warning('The verification code (' . $code . ') given for userId: ' . $user->id . ' does not match the hash in the database.', __METHOD__);
            return false;
        }

        return true;
    }

    /**
     * Returns a user’s preferences.
     *
     * @param int|null $userId The user’s ID
     * @return array The user’s preferences
     */
    public function getUserPreferences(int $userId = null): array
    {
        // TODO: Remove try/catch after next breakpoint
        try {
            $preferences = (new Query())
                ->select(['preferences'])
                ->from([Table::USERPREFERENCES])
                ->where(['userId' => $userId])
                ->scalar();

            return $preferences ? Json::decode($preferences) : [];
        } catch (DbException $e) {
            return [];
        }
    }

    /**
     * Saves a user’s preferences.
     *
     * @param User $user The user
     * @param array $preferences The user’s new preferences
     */
    public function saveUserPreferences(User $user, array $preferences)
    {
        $preferences = $user->mergePreferences($preferences);

        Craft::$app->getDb()->createCommand()
            ->upsert(
                Table::USERPREFERENCES,
                ['userId' => $user->id],
                ['preferences' => Json::encode($preferences)],
                [],
                false)
            ->execute();
    }

    /**
     * Returns one of a user’s preferences by its key.
     *
     * @param int|null $userId The user’s ID
     * @param string $key The preference’s key
     * @param mixed $default The default value, if the preference hasn’t been set
     * @return mixed The user’s preference
     */
    public function getUserPreference(int $userId = null, string $key, $default = null)
    {
        $preferences = $this->getUserPreferences($userId);
        return $preferences[$key] ?? $default;
    }

    /**
     * Sends a new account activation email for a user, regardless of their status.
     *
     * A new verification code will generated for the user overwriting any existing one.
     *
     * @param User $user The user to send the activation email to.
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
     * A new verification code be will generated for the user, overwriting any existing one.
     *
     * @param User $user The user to send the forgot password email to.
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
     * @return string The new Email Verification URL.
     */
    public function getEmailVerifyUrl(User $user): string
    {
        $fePath = Craft::$app->getConfig()->getGeneral()->getVerifyEmailPath();
        return $this->_getUserUrl($user, $fePath, Request::CP_PATH_VERIFY_EMAIL);
    }

    /**
     * Sets a new verification code on a user, and returns their new Password Reset URL.
     *
     * @param User $user The user that should get the new Password Reset URL
     * @return string The new Password Reset URL.
     */
    public function getPasswordResetUrl(User $user): string
    {
        $fePath = Craft::$app->getConfig()->getGeneral()->getSetPasswordPath();
        return $this->_getUserUrl($user, $fePath, Request::CP_PATH_SET_PASSWORD);
    }

    /**
     * Crops and saves a user’s photo.
     *
     * @param User $user the user.
     * @param string $fileLocation the local image path on server
     * @param string $filename name of the file to use, defaults to filename of $imagePath
     * @throws ImageException if the file provided is not a manipulatable image
     * @throws VolumeException if the user photo Volume is not provided or is invalid
     */
    public function saveUserPhoto(string $fileLocation, User $user, string $filename = '')
    {
        $filenameToUse = AssetsHelper::prepareAssetName($filename ?: pathinfo($fileLocation, PATHINFO_FILENAME), true, true);

        if (!Image::canManipulateAsImage(pathinfo($fileLocation, PATHINFO_EXTENSION))) {
            throw new ImageException(Craft::t('app', 'User photo must be an image that Craft can manipulate.'));
        }

        $volumes = Craft::$app->getVolumes();
        $volumeUid = Craft::$app->getProjectConfig()->get('users.photoVolumeUid');

        if (!$volumeUid || ($volume = $volumes->getVolumeByUid($volumeUid)) === null) {
            throw new VolumeException(Craft::t('app',
                'The volume set for user photo storage is not valid.'));
        }

        $subpath = (string)Craft::$app->getProjectConfig()->get('users.photoSubpath');

        if ($subpath) {
            try {
                $subpath = Craft::$app->getView()->renderObjectTemplate($subpath, $user);
            } catch (\Throwable $e) {
                throw new InvalidSubpathException($subpath);
            }
        }

        /** @var Volume $volume */
        $assetsService = Craft::$app->getAssets();

        // If the photo exists, just replace the file.
        if ($user->photoId && $user->getPhoto() !== null) {
            // No longer a new file.
            $assetsService->replaceAssetFile($assetsService->getAssetById($user->photoId), $fileLocation, $filenameToUse);
        } else {
            $folderId = $assetsService->ensureFolderByFullPathAndVolume($subpath, $volume);
            $filenameToUse = $assetsService->getNameReplacementInFolder($filenameToUse, $folderId);

            $photo = new Asset();
            $photo->setScenario(Asset::SCENARIO_CREATE);
            $photo->tempFilePath = $fileLocation;
            $photo->filename = $filenameToUse;
            $photo->newFolderId = $folderId;
            $photo->volumeId = $volume->id;

            // Save photo.
            $elementsService = Craft::$app->getElements();
            $elementsService->saveElement($photo);

            $user->setPhoto($photo);
            $elementsService->saveElement($user, false);
        }
    }

    /**
     * Deletes a user’s photo.
     *
     * @param User $user The user
     * @return bool Whether the user’s photo was deleted successfully
     */
    public function deleteUserPhoto(User $user): bool
    {
        return Craft::$app->getElements()->deleteElementById($user->photoId, Asset::class);
    }

    /**
     * Handles a valid login for a user.
     *
     * @param User $user The user
     */
    public function handleValidLogin(User $user)
    {
        $now = DateTimeHelper::currentUTCDateTime();

        // Update the User record
        $userRecord = $this->_getUserRecordById($user->id);
        $userRecord->lastLoginDate = $now;
        $userRecord->invalidLoginWindowStart = null;
        $userRecord->invalidLoginCount = null;

        if (Craft::$app->getConfig()->getGeneral()->storeUserIps) {
            $userRecord->lastLoginAttemptIp = Craft::$app->getRequest()->getUserIP();
        }

        $userRecord->save();

        // Update the User model too
        $user->lastLoginDate = $now;
        $user->invalidLoginCount = null;
    }

    /**
     * Handles an invalid login for a user.
     *
     * @param User $user The user
     */
    public function handleInvalidLogin(User $user)
    {
        $userRecord = $this->_getUserRecordById($user->id);
        $now = DateTimeHelper::currentUTCDateTime();

        $userRecord->lastInvalidLoginDate = $now;

        if (Craft::$app->getConfig()->getGeneral()->storeUserIps) {
            $userRecord->lastLoginAttemptIp = Craft::$app->getRequest()->getUserIP();
        }

        // Was that one too many?
        $maxInvalidLogins = Craft::$app->getConfig()->getGeneral()->maxInvalidLogins;
        $alreadyLocked = $user->locked;

        if ($maxInvalidLogins) {
            if ($this->_isUserInsideInvalidLoginWindow($userRecord)) {
                $userRecord->invalidLoginCount++;

                // Was that one bad password too many?
                if ($userRecord->invalidLoginCount >= $maxInvalidLogins) {
                    $userRecord->locked = true;
                    $userRecord->invalidLoginCount = null;
                    $userRecord->invalidLoginWindowStart = null;
                    $userRecord->lockoutDate = $now;

                    $user->locked = true;
                    $user->lockoutDate = $now;
                }
            } else {
                // Start the invalid login window and counter
                $userRecord->invalidLoginWindowStart = $now;
                $userRecord->invalidLoginCount = 1;
            }

            // Update the counter on the user model
            $user->invalidLoginCount = $userRecord->invalidLoginCount;
        }

        $userRecord->save();

        // Update the User model too
        $user->lastInvalidLoginDate = $now;

        if (!$alreadyLocked && $user->locked && $this->hasEventHandlers(self::EVENT_AFTER_LOCK_USER)) {
            // Fire an 'afterLockUser' event
            $this->trigger(self::EVENT_AFTER_LOCK_USER, new UserEvent([
                'user' => $user,
            ]));
        }
    }

    /**
     * Activates a user, bypassing email verification.
     *
     * @param User $user The user.
     * @return bool Whether the user was activated successfully.
     * @throws \Throwable if reasons
     */
    public function activateUser(User $user): bool
    {
        // Fire a 'beforeActivateUser' event
        $event = new UserEvent([
            'user' => $user,
        ]);
        $this->trigger(self::EVENT_BEFORE_ACTIVATE_USER, $event);

        if (!$event->isValid) {
            return false;
        }

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            $userRecord = $this->_getUserRecordById($user->id);
            $userRecord->pending = false;
            $userRecord->verificationCode = null;
            $userRecord->verificationCodeIssuedDate = null;
            $userRecord->save();

            $user->pending = false;

            // If they have an unverified email address, now is the time to set it to their primary email address
            $this->verifyEmailForUser($user);

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Fire an 'afterActivateUser' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_ACTIVATE_USER)) {
            $this->trigger(self::EVENT_AFTER_ACTIVATE_USER, new UserEvent([
                'user' => $user
            ]));
        }

        return true;
    }

    /**
     * If 'unverifiedEmail' is set on the User, then this method will transfer it to the official email property
     * and clear the unverified one.
     *
     * @param User $user
     * @return bool
     */
    public function verifyEmailForUser(User $user): bool
    {
        // Bail if they don't have an unverified email to begin with
        if (!$user->unverifiedEmail) {
            return true;
        }

        $userRecord = $this->_getUserRecordById($user->id);
        $userRecord->email = $user->unverifiedEmail;
        $userRecord->unverifiedEmail = null;

        if (Craft::$app->getConfig()->getGeneral()->useEmailAsUsername) {
            $userRecord->username = $user->unverifiedEmail;
        }

        if (!$userRecord->save()) {
            $user->addErrors($userRecord->getErrors());
            return false;
        }

        // If the user status is pending, let's activate them.
        if ($userRecord->pending == true) {
            $this->activateUser($user);
        }

        return true;
    }

    /**
     * Unlocks a user, bypassing the cooldown phase.
     *
     * @param User $user The user.
     * @return bool Whether the user was unlocked successfully.
     * @throws \Throwable if reasons
     */
    public function unlockUser(User $user): bool
    {
        // Fire a 'beforeUnlockUser' event
        $event = new UserEvent([
            'user' => $user,
        ]);
        $this->trigger(self::EVENT_BEFORE_UNLOCK_USER, $event);

        if (!$event->isValid) {
            return false;
        }

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            $userRecord = $this->_getUserRecordById($user->id);
            $userRecord->locked = false;
            $userRecord->invalidLoginCount = null;
            $userRecord->invalidLoginWindowStart = null;
            $userRecord->lockoutDate = null;
            $userRecord->save();

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Update the User model too
        $user->locked = false;
        $user->invalidLoginCount = null;
        $user->lockoutDate = null;

        // Fire an 'afterUnlockUser' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_UNLOCK_USER)) {
            $this->trigger(self::EVENT_AFTER_UNLOCK_USER, new UserEvent([
                'user' => $user
            ]));
        }

        return true;
    }

    /**
     * Suspends a user.
     *
     * @param User $user The user.
     * @return bool Whether the user was suspended successfully.
     * @throws \Throwable if reasons
     */
    public function suspendUser(User $user): bool
    {
        // Fire a 'beforeSuspendUser' event
        $event = new UserEvent([
            'user' => $user,
        ]);
        $this->trigger(self::EVENT_BEFORE_SUSPEND_USER, $event);

        if (!$event->isValid) {
            return false;
        }

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            $userRecord = $this->_getUserRecordById($user->id);
            $userRecord->suspended = true;
            $user->suspended = true;
            $userRecord->save();

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Fire an 'afterSuspendUser' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SUSPEND_USER)) {
            $this->trigger(self::EVENT_AFTER_SUSPEND_USER, new UserEvent([
                'user' => $user
            ]));
        }

        return true;
    }

    /**
     * Unsuspends a user.
     *
     * @param User $user The user.
     * @return bool Whether the user was unsuspended successfully.
     * @throws \Throwable if reasons
     */
    public function unsuspendUser(User $user): bool
    {
        // Fire a 'beforeUnsuspendUser' event
        $event = new UserEvent([
            'user' => $user,
        ]);
        $this->trigger(self::EVENT_BEFORE_UNSUSPEND_USER, $event);

        if (!$event->isValid) {
            return false;
        }

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            $userRecord = $this->_getUserRecordById($user->id);
            $userRecord->suspended = false;
            $userRecord->save();

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();

            throw $e;
        }

        // Update the User model too
        $user->suspended = false;

        // Fire an 'afterUnsuspendUser' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_UNSUSPEND_USER)) {
            $this->trigger(self::EVENT_AFTER_UNSUSPEND_USER, new UserEvent([
                'user' => $user
            ]));
        }

        return true;
    }

    /**
     * Shuns a message for a user.
     *
     * @param int $userId The user’s ID.
     * @param string $message The message to be shunned.
     * @param DateTime|null $expiryDate When the message should be un-shunned. Defaults to `null` (never un-shun).
     * @return bool Whether the message was shunned successfully.
     */
    public function shunMessageForUser(int $userId, string $message, DateTime $expiryDate = null): bool
    {
        $affectedRows = Craft::$app->getDb()->createCommand()
            ->upsert(
                Table::SHUNNEDMESSAGES,
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
     * @param int $userId The user’s ID.
     * @param string $message The message to un-shun.
     * @return bool Whether the message was un-shunned successfully.
     */
    public function unshunMessageForUser(int $userId, string $message): bool
    {
        $affectedRows = Craft::$app->getDb()->createCommand()
            ->delete(
                Table::SHUNNEDMESSAGES,
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
     * @param int $userId The user’s ID.
     * @param string $message The message to check.
     * @return bool Whether the user has shunned the message.
     */
    public function hasUserShunnedMessage(int $userId, string $message): bool
    {
        return (new Query())
            ->from([Table::SHUNNEDMESSAGES])
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
     * Deletes any pending users that have shown zero sense of urgency and are
     * just taking up space.
     *
     * This method will check the <config:purgePendingUsersDuration> config
     * setting, and if it is set to a valid duration, it will delete any user
     * accounts that were created that duration ago, and have still not
     * activated their account.
     */
    public function purgeExpiredPendingUsers()
    {
        $generalConfig = Craft::$app->getConfig()->getGeneral();

        if ($generalConfig->purgePendingUsersDuration === 0) {
            return;
        }

        $interval = DateTimeHelper::secondsToInterval($generalConfig->purgePendingUsersDuration);
        $expire = DateTimeHelper::currentUTCDateTime();
        $pastTime = $expire->sub($interval);

        $query = User::find()
            ->status('pending')
            ->andWhere(['<', 'users.verificationCodeIssuedDate', Db::prepareDateForDb($pastTime)]);

        $elementsService = Craft::$app->getElements();

        foreach ($query->each() as $user) {
            $elementsService->deleteElement($user);
            Craft::info("Just deleted pending user {$user->username} ({$user->id}), because they took too long to activate their account.", __METHOD__);
        }
    }

    /**
     * Assigns a user to a given list of user groups.
     *
     * @param int $userId The user’s ID
     * @param int[] $groupIds The groups’ IDs. Pass an empty array to remove a user from all groups.
     * @return bool Whether the users were successfully assigned to the groups.
     */
    public function assignUserToGroups(int $userId, array $groupIds): bool
    {
        // Fire a 'beforeAssignUserToGroups' event
        $event = new UserGroupsAssignEvent([
            'userId' => $userId,
            'groupIds' => $groupIds
        ]);
        $this->trigger(self::EVENT_BEFORE_ASSIGN_USER_TO_GROUPS, $event);

        if (!$event->isValid) {
            return false;
        }

        // Delete their existing groups
        Craft::$app->getDb()->createCommand()
            ->delete(Table::USERGROUPS_USERS, ['userId' => $userId])
            ->execute();

        if (!empty($groupIds)) {
            // Add the new ones
            $values = [];
            foreach ($groupIds as $groupId) {
                $values[] = [$groupId, $userId];
            }

            Craft::$app->getDb()->createCommand()
                ->batchInsert(
                    Table::USERGROUPS_USERS,
                    [
                        'groupId',
                        'userId'
                    ],
                    $values)
                ->execute();
        }

        // Fire an 'afterAssignUserToGroups' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_ASSIGN_USER_TO_GROUPS)) {
            $this->trigger(self::EVENT_AFTER_ASSIGN_USER_TO_GROUPS, new UserGroupsAssignEvent([
                'userId' => $userId,
                'groupIds' => $groupIds
            ]));
        }

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

    /**
     * Assigns a user to the default user group.
     *
     * This method is called toward the end of a public registration request.
     *
     * @param User $user The user that was just registered.
     * @return bool Whether the user was assigned to the default group.
     */
    public function assignUserToDefaultGroup(User $user): bool
    {
        // Make sure there's a default group
        $uid = Craft::$app->getProjectConfig()->get('users.defaultGroup');

        if (!$uid) {
            return false;
        }

        $group = Craft::$app->getUserGroups()->getGroupByUid($uid);

        if (!$group) {
            return false;
        }

        // Fire a 'beforeAssignUserToDefaultGroup' event
        $event = new UserAssignGroupEvent([
            'user' => $user
        ]);
        $this->trigger(self::EVENT_BEFORE_ASSIGN_USER_TO_DEFAULT_GROUP, $event);

        if (!$event->isValid) {
            return false;
        }

        if (!$this->assignUserToGroups($user->id, [$group->id])) {
            return false;
        }

        // Fire an 'afterAssignUserToDefaultGroup' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_ASSIGN_USER_TO_DEFAULT_GROUP)) {
            $this->trigger(self::EVENT_AFTER_ASSIGN_USER_TO_DEFAULT_GROUP, new UserAssignGroupEvent([
                'user' => $user
            ]));
        }

        return true;
    }

    /**
     * Handle user field layout changes.
     *
     * @param ConfigEvent $event
     */
    public function handleChangedUserFieldLayout(ConfigEvent $event)
    {
        // Use this because we want this to trigger this if anything changes inside but ONLY ONCE
        static $parsed = false;
        if ($parsed) {
            return;
        }

        $parsed = true;
        $data = Craft::$app->getProjectConfig()->get(self::CONFIG_USERLAYOUT_KEY, true);

        $fieldsService = Craft::$app->getFields();

        if (empty($data) || empty($config = reset($data))) {
            $fieldsService->deleteLayoutsByType(User::class);
            return;
        }

        // Make sure fields are processed
        ProjectConfigHelper::ensureAllFieldsProcessed();

        // Save the field layout
        $layout = FieldLayout::createFromConfig($config);
        $layout->id = $fieldsService->getLayoutByType(User::class)->id;
        $layout->type = User::class;
        $layout->uid = key($data);
        $fieldsService->saveLayout($layout);
    }

    /**
     * Save the user field layout
     *
     * @param FieldLayout $layout
     * @return bool
     */
    public function saveLayout(FieldLayout $layout)
    {
        $projectConfig = Craft::$app->getProjectConfig();
        $fieldLayoutConfig = $layout->getConfig();
        $uid = StringHelper::UUID();

        $projectConfig->set(self::CONFIG_USERLAYOUT_KEY, [$uid => $fieldLayoutConfig], "Save the user field layout");
        return true;
    }

    /**
     * Returns whether a user is allowed to impersonate another user.
     *
     * @param User $impersonator
     * @param User $impersonatee
     * @return bool
     * @since 3.2.0
     */
    public function canImpersonate(User $impersonator, User $impersonatee): bool
    {
        // Admins can do whatever they want
        if ($impersonator->admin) {
            return true;
        }

        // Only admins are allowed to impersonate another admin
        if ($impersonatee->admin) {
            return false;
        }

        // impersonateUsers permission is obviously required
        if (!$impersonator->can('impersonateUsers')) {
            return false;
        }

        // Make sure the impersonator has at least all the same permissions as the impersonatee
        $permissionsService = Craft::$app->getUserPermissions();
        $impersonatorPermissions = array_flip($permissionsService->getPermissionsByUserId($impersonator->id));
        $impersonateePermissions = $permissionsService->getPermissionsByUserId($impersonatee->id);

        foreach ($impersonateePermissions as $permission) {
            if (!isset($impersonatorPermissions[$permission])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Prune a deleted field from user group layout.
     *
     * @param FieldEvent $event
     */
    public function pruneDeletedField(FieldEvent $event)
    {
        /** @var Field $field */
        $field = $event->field;
        $fieldUid = $field->uid;

        $projectConfig = Craft::$app->getProjectConfig();
        $fieldLayouts = $projectConfig->get(self::CONFIG_USERLAYOUT_KEY);

        // Engage stealth mode
        $projectConfig->muteEvents = true;

        // Prune the user field layout.
        if (is_array($fieldLayouts)) {
            foreach ($fieldLayouts as $layoutUid => $layout) {
                if (!empty($layout['tabs'])) {
                    foreach ($layout['tabs'] as $tabUid => $tab) {
                        $projectConfig->remove(self::CONFIG_USERLAYOUT_KEY . '.' . $layoutUid . '.tabs.' . $tabUid . '.fields.' . $fieldUid, 'Prune deleted field');
                    }
                }
            }
        }

        // Nuke all the layout fields from the DB
        Craft::$app->getDb()->createCommand()->delete('{{%fieldlayoutfields}}', ['fieldId' => $field->id])->execute();

        // Allow events again
        $projectConfig->muteEvents = false;
    }

    /**
     * Gets a user record by its ID.
     *
     * @param int $userId
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
     * @param UserRecord $userRecord
     * @return string
     */
    private function _setVerificationCodeOnUserRecord(UserRecord $userRecord): string
    {
        $securityService = Craft::$app->getSecurity();
        $unhashedCode = $securityService->generateRandomString(32);

        // Strip underscores so they don't get interpreted as italics markers in the Markdown parser
        $unhashedCode = str_replace('_', StringHelper::randomString(1), $unhashedCode);

        $hashedCode = $securityService->hashPassword($unhashedCode);
        $userRecord->verificationCode = $hashedCode;
        $userRecord->verificationCodeIssuedDate = DateTimeHelper::currentUTCDateTime();

        return $unhashedCode;
    }

    /**
     * Determines if a user is within their invalid login window.
     *
     * @param UserRecord $userRecord
     * @return bool
     */
    private function _isUserInsideInvalidLoginWindow(UserRecord $userRecord): bool
    {
        // If we don't even know the last time they logged in, they're good
        if (!$userRecord->invalidLoginWindowStart) {
            return false;
        }

        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $interval = DateTimeHelper::secondsToInterval($generalConfig->invalidLoginWindowDuration);
        $invalidLoginWindowStart = DateTimeHelper::toDateTime($userRecord->invalidLoginWindowStart);
        $end = $invalidLoginWindowStart->add($interval);

        return ($end >= DateTimeHelper::currentUTCDateTime());
    }

    /**
     * Sets a new verification code on a user, and returns a verification URL.
     *
     * @param User $user The user that should get the new Password Reset URL
     * @param string $fePath The path to use if we end up linking to the front end
     * @param string $cpPath The path to use if we end up linking to the control panel
     * @return string
     * @see getPasswordResetUrl()
     * @see getEmailVerifyUrl()
     */
    private function _getUserUrl(User $user, string $fePath, string $cpPath): string
    {
        $userRecord = $this->_getUserRecordById($user->id);
        $unhashedVerificationCode = $this->_setVerificationCodeOnUserRecord($userRecord);
        $userRecord->save();

        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $params = [
            'code' => $unhashedVerificationCode,
            'id' => $user->uid
        ];

        $scheme = UrlHelper::getSchemeForTokenizedUrl();

        if (!$user->can('accessCp')) {
            return UrlHelper::siteUrl($fePath, $params, $scheme);
        }

        // Only use cpUrl() if the base CP URL has been explicitly set,
        // so UrlHelper won't use HTTP_HOST
        if ($generalConfig->baseCpUrl) {
            return UrlHelper::cpUrl($cpPath, $params, $scheme);
        }

        $path = $generalConfig->cpTrigger . '/' . $cpPath;
        return UrlHelper::siteUrl($path, $params, $scheme);
    }
}
