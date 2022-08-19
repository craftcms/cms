<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\db\Query;
use craft\db\Table;
use craft\elements\Asset;
use craft\elements\User;
use craft\errors\ImageException;
use craft\errors\InvalidSubpathException;
use craft\errors\UserNotFoundException;
use craft\errors\VolumeException;
use craft\events\ConfigEvent;
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
use craft\models\Volume;
use craft\records\User as UserRecord;
use craft\web\Request;
use DateTime;
use DateTimeZone;
use Throwable;
use yii\base\Component;
use yii\base\Exception;
use yii\base\InvalidArgumentException;

/**
 * The Users service provides APIs for managing users.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getUsers()|`Craft::$app->users`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Users extends Component
{
    /**
     * @event UserEvent The event that is triggered before a user’s email is verified.
     */
    public const EVENT_BEFORE_VERIFY_EMAIL = 'beforeVerifyEmail';

    /**
     * @event UserEvent The event that is triggered after a user’s email is verified.
     */
    public const EVENT_AFTER_VERIFY_EMAIL = 'afterVerifyEmail';

    /**
     * @event UserEvent The event that is triggered before a user is activated.
     *
     * You may set [[\craft\events\CancelableEvent::$isValid]] to `false` to prevent the user from getting activated.
     */
    public const EVENT_BEFORE_ACTIVATE_USER = 'beforeActivateUser';

    /**
     * @event UserEvent The event that is triggered after a user is activated.
     */
    public const EVENT_AFTER_ACTIVATE_USER = 'afterActivateUser';

    /**
     * @event UserEvent The event that is triggered before a user is deactivated.
     *
     * You may set [[UserEvent::isValid]] to `false` to prevent the user from getting deactivated.
     *
     * @since 4.0.0
     */
    public const EVENT_BEFORE_DEACTIVATE_USER = 'beforeDeactivateUser';

    /**
     * @event UserEvent The event that is triggered after a user is deactivated.
     * @since 4.0.0
     */
    public const EVENT_AFTER_DEACTIVATE_USER = 'afterDeactivateUser';

    /**
     * @event UserEvent The event that is triggered after a user is locked.
     */
    public const EVENT_AFTER_LOCK_USER = 'afterLockUser';

    /**
     * @event UserEvent The event that is triggered before a user is unlocked.
     *
     * You may set [[\craft\events\CancelableEvent::$isValid]] to `false` to prevent the user from getting unlocked.
     */
    public const EVENT_BEFORE_UNLOCK_USER = 'beforeUnlockUser';

    /**
     * @event UserEvent The event that is triggered after a user is unlocked.
     */
    public const EVENT_AFTER_UNLOCK_USER = 'afterUnlockUser';

    /**
     * @event UserEvent The event that is triggered before a user is suspended.
     *
     * You may set [[\craft\events\CancelableEvent::$isValid]] to `false` to prevent the user from getting suspended.
     */
    public const EVENT_BEFORE_SUSPEND_USER = 'beforeSuspendUser';

    /**
     * @event UserEvent The event that is triggered after a user is suspended.
     */
    public const EVENT_AFTER_SUSPEND_USER = 'afterSuspendUser';

    /**
     * @event UserEvent The event that is triggered before a user is unsuspended.
     *
     * You may set [[\craft\events\CancelableEvent::isValid]] to `false` to prevent the user from getting unsuspended.
     */
    public const EVENT_BEFORE_UNSUSPEND_USER = 'beforeUnsuspendUser';

    /**
     * @event UserEvent The event that is triggered after a user is unsuspended.
     */
    public const EVENT_AFTER_UNSUSPEND_USER = 'afterUnsuspendUser';

    /**
     * @event UserGroupsAssignEvent The event that is triggered before a user is assigned to some user groups.
     *
     * You may set [[\craft\events\CancelableEvent::$isValid]] to `false` to prevent the user from getting assigned to the groups.
     */
    public const EVENT_BEFORE_ASSIGN_USER_TO_GROUPS = 'beforeAssignUserToGroups';

    /**
     * @event UserGroupsAssignEvent The event that is triggered after a user is assigned to some user groups.
     */
    public const EVENT_AFTER_ASSIGN_USER_TO_GROUPS = 'afterAssignUserToGroups';

    /**
     * @event UserAssignGroupEvent The event that is triggered before a user is assigned to the default user group.
     *
     * You may set [[\craft\events\CancelableEvent::$isValid]] to `false` to prevent the user from getting assigned to the default
     * user group.
     */
    public const EVENT_BEFORE_ASSIGN_USER_TO_DEFAULT_GROUP = 'beforeAssignUserToDefaultGroup';

    /**
     * @event UserAssignGroupEvent The event that is triggered after a user is assigned to the default user group.
     */
    public const EVENT_AFTER_ASSIGN_USER_TO_DEFAULT_GROUP = 'afterAssignUserToDefaultGroup';

    /**
     * Returns a user by an email address, creating one if none already exists.
     *
     * @param string $email
     * @return User
     * @throws InvalidArgumentException if `$email` is invalid
     * @throws Exception if the user couldn’t be saved for some unexpected reason
     * @since 4.0.0
     */
    public function ensureUserByEmail(string $email): User
    {
        /** @var User|null $user */
        $user = User::find()
            ->email($email)
            ->status(null)
            ->one();

        if (!$user) {
            $user = new User();
            $user->email = $email;
            if (!$user->validate(['email'])) {
                throw new InvalidArgumentException($user->getFirstError('email'));
            }
            if (!Craft::$app->getElements()->saveElement($user, false)) {
                throw new Exception('Unable to save user: ' . implode(', ', $user->getFirstErrors()));
            }
        }

        return $user;
    }

    /**
     * @var array Cached user preferences.
     * @see getUserPreferences()
     */
    private array $_userPreferences = [];

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
    public function getUserById(int $userId): ?User
    {
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
    public function getUserByUsernameOrEmail(string $usernameOrEmail): ?User
    {
        $query = User::find()
            ->addSelect(['users.password', 'users.passwordResetRequired'])
            ->status(null);

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

        /** @var User|null */
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
    public function getUserByUid(string $uid): ?User
    {
        /** @var User|null */
        return User::find()
            ->uid($uid)
            ->status(null)
            ->one();
    }

    /**
     * Returns whether a verification code is valid for the given user.
     *
     * This method first checks if the code has expired past the
     * <config4:verificationCodeDuration> config setting. If it is still valid,
     * then, the checks the validity of the contents of the code.
     *
     * @param User $user The user to check the code for.
     * @param string $code The verification code to check for.
     * @return bool Whether the code is still valid.
     */
    public function isVerificationCodeValidForUser(User $user, string $code): bool
    {
        if (!$user->verificationCode || !$user->verificationCodeIssuedDate) {
            // Fetch from the DB
            $userRecord = $this->_getUserRecordById($user->id);
            $user->verificationCode = $userRecord->verificationCode;
            $user->verificationCodeIssuedDate = $userRecord->verificationCodeIssuedDate
                ? new DateTime($userRecord->verificationCodeIssuedDate, new DateTimeZone('UTC'))
                : null;

            if (!$user->verificationCode || !$user->verificationCodeIssuedDate) {
                return false;
            }
        }

        // Make sure the verification code isn't expired
        $minCodeIssueDate = DateTimeHelper::currentUTCDateTime();
        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $interval = DateTimeHelper::secondsToInterval($generalConfig->verificationCodeDuration);
        $minCodeIssueDate->sub($interval);

        // Make sure it’s not expired
        if ($user->verificationCodeIssuedDate < $minCodeIssueDate) {
            $userRecord = $userRecord ?? $this->_getUserRecordById($user->id);
            $userRecord->verificationCode = $user->verificationCode = null;
            $userRecord->verificationCodeIssuedDate = $user->verificationCodeIssuedDate = null;
            $userRecord->save();

            Craft::warning('The verification code (' . $code . ') given for userId: ' . $user->id . ' is expired.', __METHOD__);
            return false;
        }

        try {
            $valid = Craft::$app->getSecurity()->validatePassword($code, $user->verificationCode);
        } catch (InvalidArgumentException) {
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
     * @param int $userId The user’s ID
     * @return array The user’s preferences
     */
    public function getUserPreferences(int $userId): array
    {
        if (!isset($this->_userPreferences[$userId])) {
            $preferences = (new Query())
                ->select(['preferences'])
                ->from([Table::USERPREFERENCES])
                ->where(['userId' => $userId])
                ->scalar();

            $this->_userPreferences[$userId] = $preferences ? Json::decode($preferences) : [];
        }

        return $this->_userPreferences[$userId];
    }

    /**
     * Saves a user’s preferences.
     *
     * @param User $user The user
     * @param array $preferences The user’s new preferences
     */
    public function saveUserPreferences(User $user, array $preferences): void
    {
        // Merge in any other saved preferences
        $preferences += $this->getUserPreferences($user->id);

        Db::upsert(Table::USERPREFERENCES, [
            'userId' => $user->id,
            'preferences' => Json::encode($preferences),
        ]);

        $this->_userPreferences[$user->id] = $preferences;
    }

    /**
     * Returns one of a user’s preferences by its key.
     *
     * @param int $userId The user’s ID
     * @param string $key The preference’s key
     * @param mixed $default The default value, if the preference hasn’t been set
     * @return mixed The user’s preference
     */
    public function getUserPreference(int $userId, string $key, mixed $default = null): mixed
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
        $url = $this->getActivationUrl($user);

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
     * Sets a new verification code on a user, and returns their activation URL.
     *
     * @param User $user
     * @return string
     */
    public function getActivationUrl(User $user): string
    {
        // If the user doesn't have a password yet, use a Password Reset URL
        if (!$user->password) {
            return $this->getPasswordResetUrl($user);
        }

        return $this->getEmailVerifyUrl($user);
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
     * Removes credentials for a user.
     *
     * @param User $user The user that should have credentials removed.
     * @return bool Whether the user’s credentials were successfully removed.
     * @throws UserNotFoundException
     * @since 4.0.0
     */
    public function removeCredentials(User $user): bool
    {
        $userRecord = $this->_getUserRecordById($user->id);
        $userRecord->active = false;
        $userRecord->pending = false;
        $userRecord->password = null;
        $userRecord->verificationCode = null;

        if (!$userRecord->save()) {
            return false;
        }

        $user->active = false;
        $user->pending = false;
        $user->password = null;
        $user->verificationCode = null;
        return true;
    }

    /**
     * Crops and saves a user’s photo.
     *
     * @param User $user the user.
     * @param string $fileLocation the local image path on server
     * @param string|null $filename name of the file to use, defaults to filename of `$fileLocation`
     * @throws ImageException if the file provided is not a manipulatable image
     * @throws VolumeException if the user photo Volume is not provided or is invalid
     */
    public function saveUserPhoto(string $fileLocation, User $user, ?string $filename = null): void
    {
        $filename = AssetsHelper::prepareAssetName($filename ?? pathinfo($fileLocation, PATHINFO_BASENAME), true, true);

        if (!Image::canManipulateAsImage(pathinfo($fileLocation, PATHINFO_EXTENSION))) {
            throw new ImageException(Craft::t('app', 'User photo must be an image that Craft can manipulate.'));
        }

        $assetsService = Craft::$app->getAssets();

        // If the photo exists, just replace the file.
        if ($user->photoId && ($photo = $user->getPhoto()) !== null) {
            $assetsService->replaceAssetFile($photo, $fileLocation, $filename);
        } else {
            $volume = $this->_userPhotoVolume();
            $folderId = $this->_userPhotoFolderId($user, $volume);
            $filename = $assetsService->getNameReplacementInFolder($filename, $folderId);

            $photo = new Asset();
            $photo->setScenario(Asset::SCENARIO_CREATE);
            $photo->tempFilePath = $fileLocation;
            $photo->setFilename($filename);
            $photo->newFolderId = $folderId;
            $photo->setVolumeId($volume->id);

            // Save photo.
            $elementsService = Craft::$app->getElements();
            $elementsService->saveElement($photo);

            $user->setPhoto($photo);
            $elementsService->saveElement($user, false);
        }
    }

    /**
     * Updates the location of a user’s photo.
     *
     * @param User $user
     * @since 3.5.14
     */
    public function relocateUserPhoto(User $user): void
    {
        if (!$user->photoId || ($photo = $user->getPhoto()) === null) {
            return;
        }

        $volume = $this->_userPhotoVolume();
        $folderId = $this->_userPhotoFolderId($user, $volume);

        if ($photo->folderId == $folderId) {
            return;
        }

        $photo->setScenario(Asset::SCENARIO_MOVE);
        $photo->avoidFilenameConflicts = true;
        $photo->newFolderId = $folderId;
        Craft::$app->getElements()->saveElement($photo);
    }

    /**
     * Returns the user photo volume.
     *
     * @return Volume
     * @throws VolumeException if no user photo volume is set, or it's set to an invalid volume UID
     */
    private function _userPhotoVolume(): Volume
    {
        $uid = Craft::$app->getProjectConfig()->get('users.photoVolumeUid');
        if (!$uid) {
            throw new VolumeException('No user photo volume is set.');
        }

        $volume = Craft::$app->getVolumes()->getVolumeByUid($uid);
        if ($volume === null) {
            throw new VolumeException("Invalid volume UID: $uid");
        }

        return $volume;
    }

    /**
     * Returns the folder that a user’s photo should be stored.
     *
     * @param User $user
     * @param Volume $volume The user photo volume
     * @return int
     * @throws VolumeException if the user photo volume doesn’t exist
     * @throws InvalidSubpathException if the user photo subpath can’t be resolved
     */
    private function _userPhotoFolderId(User $user, Volume $volume): int
    {
        $subpath = (string)Craft::$app->getProjectConfig()->get('users.photoSubpath');

        if ($subpath !== '') {
            try {
                $subpath = Craft::$app->getView()->renderObjectTemplate($subpath, $user);
            } catch (Throwable) {
                throw new InvalidSubpathException($subpath);
            }
        }

        return Craft::$app->getAssets()->ensureFolderByFullPathAndVolume($subpath, $volume)->id;
    }

    /**
     * Deletes a user’s photo.
     *
     * @param User $user The user
     * @return bool Whether the user’s photo was deleted successfully
     */
    public function deleteUserPhoto(User $user): bool
    {
        $result = Craft::$app->getElements()->deleteElementById($user->photoId, Asset::class);

        if ($result) {
            $user->setPhoto(null);
        }

        return $result;
    }

    /**
     * Handles a valid login for a user.
     *
     * @param User $user The user
     */
    public function handleValidLogin(User $user): void
    {
        $now = DateTimeHelper::currentUTCDateTime();

        // Update the User record
        $userRecord = $this->_getUserRecordById($user->id);
        $userRecord->lastLoginDate = Db::prepareDateForDb($now);
        $userRecord->invalidLoginWindowStart = null;
        $userRecord->invalidLoginCount = null;

        if (Craft::$app->getConfig()->getGeneral()->storeUserIps) {
            $userRecord->lastLoginAttemptIp = Craft::$app->getRequest()->getUserIP();
        }

        $userRecord->save();

        // Update the User model too
        $user->lastLoginDate = $now;
        $user->invalidLoginCount = null;

        // Invalidate caches
        Craft::$app->getElements()->invalidateCachesForElement($user);
    }

    /**
     * Handles an invalid login for a user.
     *
     * @param User $user The user
     */
    public function handleInvalidLogin(User $user): void
    {
        $userRecord = $this->_getUserRecordById($user->id);
        $now = DateTimeHelper::currentUTCDateTime();

        $userRecord->lastInvalidLoginDate = Db::prepareDateForDb($now);

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
                    $userRecord->lockoutDate = Db::prepareDateForDb($now);

                    $user->locked = true;
                    $user->lockoutDate = $now;
                }
            } else {
                // Start the invalid login window and counter
                $userRecord->invalidLoginWindowStart = Db::prepareDateForDb($now);
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

        // Invalidate caches
        Craft::$app->getElements()->invalidateCachesForElement($user);
    }

    /**
     * Activates a user, bypassing email verification.
     *
     * @param User $user The user.
     * @return bool Whether the user was activated successfully.
     * @throws Throwable if reasons
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
            $userRecord->active = true;
            $userRecord->pending = false;
            $userRecord->locked = false;
            $userRecord->suspended = false;
            $userRecord->verificationCode = null;
            $userRecord->verificationCodeIssuedDate = null;
            $userRecord->invalidLoginWindowStart = null;
            $userRecord->invalidLoginCount = null;
            $userRecord->lastInvalidLoginDate = null;
            $userRecord->lockoutDate = null;
            $userRecord->save();

            $user->active = true;
            $user->pending = false;
            $user->locked = false;
            $user->suspended = false;
            $user->verificationCode = null;
            $user->verificationCodeIssuedDate = null;
            $user->invalidLoginCount = null;
            $user->lastInvalidLoginDate = null;
            $user->lockoutDate = null;

            // If they have an unverified email address, now is the time to set it to their primary email address
            $this->verifyEmailForUser($user);

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Fire an 'afterActivateUser' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_ACTIVATE_USER)) {
            $this->trigger(self::EVENT_AFTER_ACTIVATE_USER, new UserEvent([
                'user' => $user,
            ]));
        }

        // Invalidate caches
        Craft::$app->getElements()->invalidateCachesForElement($user);

        return true;
    }

    /**
     * Deactivates a user.
     *
     * @param User $user The user.
     * @return bool Whether the user was deactivated successfully.
     * @throws Throwable if reasons
     * @since 4.0.0
     */
    public function deactivateUser(User $user): bool
    {
        // Fire a 'beforeActivateUser' event
        $event = new UserEvent([
            'user' => $user,
        ]);
        $this->trigger(self::EVENT_BEFORE_DEACTIVATE_USER, $event);

        if (!$event->isValid) {
            return false;
        }

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            $userRecord = $this->_getUserRecordById($user->id);
            $userRecord->active = false;
            $userRecord->pending = false;
            $userRecord->locked = false;
            $userRecord->suspended = false;
            $userRecord->verificationCode = null;
            $userRecord->verificationCodeIssuedDate = null;
            $userRecord->invalidLoginWindowStart = null;
            $userRecord->invalidLoginCount = null;
            $userRecord->lastInvalidLoginDate = null;
            $userRecord->lockoutDate = null;
            $userRecord->save();

            $user->active = false;
            $user->pending = false;
            $user->locked = false;
            $user->suspended = false;
            $user->verificationCode = null;
            $user->verificationCodeIssuedDate = null;
            $user->invalidLoginCount = null;
            $user->lastInvalidLoginDate = null;
            $user->lockoutDate = null;

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Fire an 'afterActivateUser' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_DEACTIVATE_USER)) {
            $this->trigger(self::EVENT_AFTER_DEACTIVATE_USER, new UserEvent([
                'user' => $user,
            ]));
        }

        // Invalidate caches
        Craft::$app->getElements()->invalidateCachesForElement($user);

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
        if ($userRecord->pending) {
            $this->activateUser($user);
        }

        return true;
    }

    /**
     * Unlocks a user, bypassing the cooldown phase.
     *
     * @param User $user The user.
     * @return bool Whether the user was unlocked successfully.
     * @throws Throwable if reasons
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
        } catch (Throwable $e) {
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
                'user' => $user,
            ]));
        }

        // Invalidate caches
        Craft::$app->getElements()->invalidateCachesForElement($user);

        return true;
    }

    /**
     * Suspends a user.
     *
     * @param User $user The user.
     * @return bool Whether the user was suspended successfully.
     * @throws Throwable if reasons
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

        $userRecord = $this->_getUserRecordById($user->id);
        $userRecord->suspended = true;
        $user->suspended = true;
        $userRecord->save();

        // Destroy all sessions for this user
        Db::delete(Table::SESSIONS, ['userId' => $user->id]);

        // Fire an 'afterSuspendUser' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SUSPEND_USER)) {
            $this->trigger(self::EVENT_AFTER_SUSPEND_USER, new UserEvent([
                'user' => $user,
            ]));
        }

        // Invalidate caches
        Craft::$app->getElements()->invalidateCachesForElement($user);

        return true;
    }

    /**
     * Unsuspends a user.
     *
     * @param User $user The user.
     * @return bool Whether the user was unsuspended successfully.
     * @throws Throwable if reasons
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
        } catch (Throwable $e) {
            $transaction->rollBack();

            throw $e;
        }

        // Update the User model too
        $user->suspended = false;

        // Fire an 'afterUnsuspendUser' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_UNSUSPEND_USER)) {
            $this->trigger(self::EVENT_AFTER_UNSUSPEND_USER, new UserEvent([
                'user' => $user,
            ]));
        }

        // Invalidate caches
        Craft::$app->getElements()->invalidateCachesForElement($user);

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
    public function shunMessageForUser(int $userId, string $message, ?DateTime $expiryDate = null): bool
    {
        return (bool)Db::upsert(Table::SHUNNEDMESSAGES, [
            'userId' => $userId,
            'message' => $message,
            'expiryDate' => Db::prepareDateForDb($expiryDate),
        ]);
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
        return (bool)Db::delete(Table::SHUNNEDMESSAGES, [
            'userId' => $userId,
            'message' => $message,
        ]);
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
                    'message' => $message,
                ],
                [
                    'or',
                    ['expiryDate' => null],
                    ['>', 'expiryDate', Db::prepareDateForDb(new DateTime())],
                ],
            ])
            ->exists();
    }

    /**
     * Sets a new verification code on the user’s record.
     *
     * @param User $user The user.
     * @return string The user’s brand new verification code.
     */
    public function setVerificationCodeOnUser(User $user): string
    {
        $userRecord = $this->_getUserRecordById($user->id);

        $securityService = Craft::$app->getSecurity();
        $unhashedCode = $securityService->generateRandomString(32);

        // Strip underscores so they don't get interpreted as italics markers in the Markdown parser
        $unhashedCode = str_replace('_', StringHelper::randomString(1), $unhashedCode);
        $issueDate = DateTimeHelper::currentUTCDateTime();

        $hashedCode = $securityService->hashPassword($unhashedCode);
        $userRecord->verificationCode = $hashedCode;
        $userRecord->verificationCodeIssuedDate = Db::prepareDateForDb($issueDate);

        // Make sure they are set to pending, if not already active
        if (!$userRecord->active) {
            $userRecord->pending = true;
        }

        $userRecord->save();

        $user->pending = $userRecord->pending;
        $user->verificationCode = $hashedCode;
        $user->verificationCodeIssuedDate = $issueDate;

        // Invalidate caches
        Craft::$app->getElements()->invalidateCachesForElement($user);

        return $unhashedCode;
    }

    /**
     * Deletes any pending users that have shown zero sense of urgency and are
     * just taking up space.
     *
     * This method will check the <config4:purgePendingUsersDuration> config
     * setting, and if it is set to a valid duration, it will delete any user
     * accounts that were created that duration ago, and have still not
     * activated their account.
     *
     */
    public function purgeExpiredPendingUsers(): void
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

        foreach (Db::each($query) as $user) {
            /** @var User $user */
            $elementsService->deleteElement($user);
            Craft::info("Just deleted pending user $user->username ($user->id), because they took too long to activate their account.", __METHOD__);
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
        // Get the unique, indexed group IDs
        $newGroupIds = array_flip(array_unique(array_filter($groupIds)));

        $db = Craft::$app->getDb();

        // Get the current groups
        $oldGroups = (new Query())
            ->select(['id', 'groupId'])
            ->from([Table::USERGROUPS_USERS])
            ->where(['userId' => $userId])
            ->all($db);

        $removedGroupIds = [];

        foreach ($oldGroups as $oldGroup) {
            // Is the group still selected?
            if (isset($newGroupIds[$oldGroup['groupId']])) {
                // Avoid re-inserting it
                unset($newGroupIds[$oldGroup['groupId']]);
            } else {
                $removedGroupIds[] = $oldGroup['groupId'];
            }
        }

        if (empty($removedGroupIds) && empty($newGroupIds)) {
            // Nothing to do here
            return true;
        }

        // Fire a 'beforeAssignUserToGroups' event
        $event = new UserGroupsAssignEvent([
            'userId' => $userId,
            'groupIds' => $groupIds,
            'removedGroupIds' => $removedGroupIds,
            'newGroupIds' => array_keys($newGroupIds),
        ]);
        $this->trigger(self::EVENT_BEFORE_ASSIGN_USER_TO_GROUPS, $event);

        if (!$event->isValid) {
            return false;
        }

        // Make sure the event hasn't left us with nothing to do
        if (empty($event->removedGroupIds) && empty($event->newGroupIds)) {
            return true;
        }

        $transaction = $db->beginTransaction();
        try {
            // Add the new groups
            if (!empty($event->newGroupIds)) {
                $values = [];
                foreach ($event->newGroupIds as $groupId) {
                    $values[] = [$groupId, $userId];
                }
                Db::batchInsert(Table::USERGROUPS_USERS, ['groupId', 'userId'], $values, $db);
            }

            if (!empty($event->removedGroupIds)) {
                Db::delete(Table::USERGROUPS_USERS, [
                    'userId' => $userId,
                    'groupId' => $event->removedGroupIds,
                ], [], $db);
            }

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Fire an 'afterAssignUserToGroups' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_ASSIGN_USER_TO_GROUPS)) {
            $this->trigger(self::EVENT_AFTER_ASSIGN_USER_TO_GROUPS, new UserGroupsAssignEvent([
                'userId' => $userId,
                'groupIds' => $groupIds,
                'removedGroupIds' => $event->removedGroupIds,
                'newGroupIds' => $event->newGroupIds,
            ]));
        }

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
            'user' => $user,
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
                'user' => $user,
            ]));
        }

        return true;
    }

    /**
     * Handle user field layout changes.
     *
     * @param ConfigEvent $event
     */
    public function handleChangedUserFieldLayout(ConfigEvent $event): void
    {
        $data = $event->newValue;

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
        $fieldsService->saveLayout($layout, false);

        // Invalidate user caches
        Craft::$app->getElements()->invalidateCachesForElementType(User::class);
    }

    /**
     * Save the user field layout
     *
     * @param FieldLayout $layout
     * @param bool $runValidation Whether the layout should be validated
     * @return bool
     */
    public function saveLayout(FieldLayout $layout, bool $runValidation = true): bool
    {
        if ($runValidation && !$layout->validate()) {
            Craft::info('Field layout not saved due to validation error.', __METHOD__);
            return false;
        }

        $projectConfig = Craft::$app->getProjectConfig();
        $fieldLayoutConfig = $layout->getConfig();
        $uid = StringHelper::UUID();

        $projectConfig->set(ProjectConfig::PATH_USER_FIELD_LAYOUTS, [$uid => $fieldLayoutConfig], "Save the user field layout");
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
     * Returns whether the user can suspend the given user
     *
     * @param User $suspender
     * @param User $suspendee
     * @return bool
     * @since 3.7.32
     */
    public function canSuspend(User $suspender, User $suspendee): bool
    {
        if (!$suspender->can('moderateUsers')) {
            return false;
        }

        // Even if you have moderateUsers permissions, only and admin should be able to suspend another admin.
        if (!$suspender->admin && $suspendee->admin) {
            return false;
        }

        return true;
    }

    /**
     * @deprecated in 4.0.5. Unused fields will be pruned automatically as field layouts are resaved.
     */
    public function pruneDeletedField(): void
    {
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
            throw new UserNotFoundException("No user exists with the ID '$userId'");
        }

        return $userRecord;
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
     * @param string $fePath The URL or path to use if we end up linking to the front end
     * @param string $cpPath The path to use if we end up linking to the control panel
     * @return string
     * @see getPasswordResetUrl()
     * @see getEmailVerifyUrl()
     */
    private function _getUserUrl(User $user, string $fePath, string $cpPath): string
    {
        $unhashedVerificationCode = $this->setVerificationCodeOnUser($user);

        $params = [
            'code' => $unhashedVerificationCode,
            'id' => $user->uid,
        ];

        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $cp = (
            $user->can('accessCp') ||
            ($generalConfig->headlessMode && !UrlHelper::isAbsoluteUrl($fePath))
        );
        $scheme = UrlHelper::getSchemeForTokenizedUrl($cp);

        if (!$cp) {
            return UrlHelper::siteUrl($fePath, $params, $scheme);
        }

        // Only use cpUrl() if this is a control panel request, or the base control panel URL has been explicitly set,
        // so UrlHelper won't use HTTP_HOST
        if ($generalConfig->baseCpUrl || Craft::$app->getRequest()->getIsCpRequest()) {
            return UrlHelper::cpUrl($cpPath, $params, $scheme);
        }

        $path = UrlHelper::prependCpTrigger($cpPath);
        return UrlHelper::siteUrl($path, $params, $scheme);
    }
}
