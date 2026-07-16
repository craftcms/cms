<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\events\DefineUserGroupsEvent;
use craft\events\UserAssignGroupEvent;
use craft\events\UserEvent;
use craft\events\UserGroupsAssignEvent;
use craft\events\UserPhotoEvent;
use CraftCms\Cms\Asset\Exceptions\ImageException;
use CraftCms\Cms\Asset\Exceptions\VolumeException;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Element\Exceptions\InvalidElementException;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\ProjectConfig\Events\ConfigEvent;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Users as UsersFacade;
use CraftCms\Cms\User\Data\UserGroup;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Events\DefaultUserGroupsResolving;
use CraftCms\Cms\User\Events\EmailVerified;
use CraftCms\Cms\User\Events\UserActivated;
use CraftCms\Cms\User\Events\UserActivating;
use CraftCms\Cms\User\Events\UserAssignedToDefaultGroups;
use CraftCms\Cms\User\Events\UserAssignedToGroups;
use CraftCms\Cms\User\Events\UserDeactivated;
use CraftCms\Cms\User\Events\UserDeactivating;
use CraftCms\Cms\User\Events\UserDefaultGroupsAssigning;
use CraftCms\Cms\User\Events\UserEmailVerifying;
use CraftCms\Cms\User\Events\UserGroupsAssigning;
use CraftCms\Cms\User\Events\UserLocked;
use CraftCms\Cms\User\Events\UserPhotoDeleted;
use CraftCms\Cms\User\Events\UserPhotoDeleting;
use CraftCms\Cms\User\Events\UserPhotoSaved;
use CraftCms\Cms\User\Events\UserPhotoSaving;
use CraftCms\Cms\User\Events\UserSuspended;
use CraftCms\Cms\User\Events\UserSuspending;
use CraftCms\Cms\User\Events\UserUnlocked;
use CraftCms\Cms\User\Events\UserUnlocking;
use CraftCms\Cms\User\Events\UserUnsuspended;
use CraftCms\Cms\User\Events\UserUnsuspending;
use CraftCms\Cms\User\Models\User as UserModel;
use DateTime;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Throwable;
use yii\base\Component;
use yii\base\Exception;

/**
 * The Users service provides APIs for managing users.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getUsers()|`Craft::$app->getUsers()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\User\Users} instead.
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
     * @event DefineUserGroupsEvent The event that is triggered when defining the default user groups to assign to a publicly-registered user.
     * @see getDefaultUserGroups()
     * @since 4.5.4
     */
    public const EVENT_DEFINE_DEFAULT_USER_GROUPS = 'defineDefaultUserGroups';

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
     * @event UserPhotoEvent The event that is triggered before a user photo is saved.
     * @since 4.4.0
     */
    public const EVENT_BEFORE_SAVE_USER_PHOTO = 'beforeSaveUserPhoto';

    /**
     * @event UserPhotoEvent The event that is triggered after a user photo is saved.
     * @since 4.4.0
     */
    public const EVENT_AFTER_SAVE_USER_PHOTO = 'afterSaveUserPhoto';

    /**
     * @event UserPhotoEvent The event that is triggered before a user photo is deleted.
     * @since 4.4.0
     */
    public const EVENT_BEFORE_DELETE_USER_PHOTO = 'beforeDeleteUserPhoto';

    /**
     * @event UserPhotoEvent The event that is triggered after a user photo is deleted.
     * @since 4.4.0
     */
    public const EVENT_AFTER_DELETE_USER_PHOTO = 'beforeDeleteUserPhoto';

    /**
     * Returns a user by an email address, creating one if none already exists.
     *
     * @param string $email
     *
     * @return User
     * @throws Exception if the user couldn’t be saved for some unexpected reason
     * @since 4.0.0
     */
    public function ensureUserByEmail(string $email): User
    {
        return UsersFacade::ensureUserByEmail($email);
    }

    /**
     * Returns a user by their ID.
     *
     * ```php
     * $user = Craft::$app->users->getUserById($userId);
     * ```
     *
     * @param int $userId The user’s ID.
     *
     * @return User|null The user with the given ID, or `null` if a user could not be found.
     */
    public function getUserById(int $userId): ?User
    {
        /** @var User|null */
        return Elements::getElementById($userId, User::class);
    }

    /**
     * Returns a user by their username or email.
     *
     * ```php
     * $user = Craft::$app->users->getUserByUsernameOrEmail($loginName);
     * ```
     *
     * @param string $usernameOrEmail The user’s username or email.
     *
     * @return User|null The user with the given username/email, or `null` if a user could not be found.
     */
    public function getUserByUsernameOrEmail(string $usernameOrEmail): ?User
    {
        return UsersFacade::getUserByUsernameOrEmail($usernameOrEmail);
    }

    /**
     * Returns a user by their UID.
     *
     * ```php
     * $user = Craft::$app->users->getUserByUid($userUid);
     * ```
     *
     * @param string $uid The user’s UID.
     *
     * @return User|null The user with the given UID, or `null` if a user could not be found.
     */
    public function getUserByUid(string $uid): ?User
    {
        return UsersFacade::getUserByUid($uid);
    }

    /**
     * Returns whether a verification code is valid for the given user.
     *
     * This method first checks if the code has expired past the
     * <config5:verificationCodeDuration> config setting. If it is still valid,
     * then, the checks the validity of the contents of the code.
     *
     * @param User $user The user to check the code for.
     * @param string $code The verification code to check for.
     *
     * @return bool Whether the code is still valid.
     * @deprecated 6.0.0. Use `Password::tokenExists($user, $code)`
     */
    public function isVerificationCodeValidForUser(User $user, string $code): bool
    {
        /** @var \Illuminate\Auth\Passwords\PasswordBroker $broker */
        $broker = Password::broker();

        if ($broker->tokenExists($user, $code)) {
            return true;
        }

        if (!Schema::hasColumn(Table::USERS, 'verificationCode')) {
            return false;
        }

        if (!$user->verificationCode || !$user->verificationCodeIssuedDate) {
            // Fetch from the DB
            $userModel = UserModel::findOrFail($user->id);

            $user->verificationCode = $userModel->verificationCode;
            $user->verificationCodeIssuedDate = $userModel->verificationCodeIssuedDate?->setTimezone('UTC')->toDateTime();

            if (!$user->verificationCode || !$user->verificationCodeIssuedDate) {
                return false;
            }
        }

        // Make sure the verification code isn't expired
        $minCodeIssueDate = now()->subMinutes((int) config('auth.passwords.craft.expire', 1440))->toDateTime();

        // Make sure it’s not expired
        if ($user->verificationCodeIssuedDate < $minCodeIssueDate) {
            $userModel ??= UserModel::findOrFail($user->id);
            $userModel->verificationCode = $user->verificationCode = null;
            $userModel->verificationCodeIssuedDate = $user->verificationCodeIssuedDate = null;
            $userModel->save();

            Log::warning('The verification code (' . $code . ') given for userId: ' . $user->id . ' is expired.', [__METHOD__]);

            return false;
        }

        try {
            $valid = Craft::$app->getSecurity()->validatePassword($code, $user->verificationCode);
        } catch (InvalidArgumentException) {
            $valid = false;
        }

        if (!$valid) {
            Log::warning('The verification code (' . $code . ') given for userId: ' . $user->id . ' does not match the hash in the database.', [__METHOD__]);

            return false;
        }

        return true;
    }

    /**
     * Returns a user’s preferences.
     *
     * @param int $userId The user’s ID
     *
     * @return array The user’s preferences
     */
    public function getUserPreferences(int $userId): array
    {
        return UsersFacade::getUserPreferences($userId);
    }

    /**
     * Saves a user’s preferences.
     *
     * @param User $user The user
     * @param array $preferences The user’s new preferences
     */
    public function saveUserPreferences(User $user, array $preferences): void
    {
        UsersFacade::saveUserPreferences($user, $preferences);
    }

    /**
     * Returns one of a user’s preferences by its key.
     *
     * @param int $userId The user’s ID
     * @param string $key The preference’s key
     * @param mixed $default The default value, if the preference hasn’t been set
     *
     * @return mixed The user’s preference
     */
    public function getUserPreference(int $userId, string $key, mixed $default = null): mixed
    {
        return UsersFacade::getUserPreference($userId, $key, $default);
    }

    /**
     * Sends a new account activation email for a user, regardless of their status.
     *
     * A new verification code will generated for the user overwriting any existing one.
     *
     * @param User $user The user to send the activation email to.
     *
     * @return bool Whether the email was sent successfully.
     * @throws InvalidElementException if the user doesn't validate
     */
    public function sendActivationEmail(User $user): bool
    {
        $user->sendEmailVerificationNotification();

        return true;
    }

    /**
     * Sends a new email verification email to a user, regardless of their status.
     *
     * A new verification code will generated for the user overwriting any existing one.
     *
     * @param User $user The user to send the activation email to.
     *
     * @return bool Whether the email was sent successfully.
     * @throws InvalidElementException if the user doesn't validate
     */
    public function sendNewEmailVerifyEmail(User $user): bool
    {
        $user->sendEmailVerificationNotification();

        return true;
    }

    /**
     * Sends a password reset email to a user.
     *
     * A new verification code be will generated for the user, overwriting any existing one.
     *
     * @param User $user The user to send the forgot password email to.
     *
     * @return bool Whether the email was sent successfully.
     * @throws InvalidElementException if the user doesn't validate
     */
    public function sendPasswordResetEmail(User $user): bool
    {
        return UsersFacade::sendPasswordResetEmail($user);
    }

    /**
     * Sets a new verification code on a user, and returns their activation URL.
     *
     * @param User $user
     *
     * @return string
     * @throws InvalidElementException if the user doesn't validate
     */
    public function getActivationUrl(User $user): string
    {
        return UsersFacade::getActivationUrl($user);
    }

    /**
     * Sets a new verification code on a user, and returns their new Email Verification URL.
     *
     * @param User $user The user that should get the new Email Verification URL.
     *
     * @return string The new Email Verification URL.
     * @throws InvalidElementException if the user doesn't validate
     */
    public function getEmailVerifyUrl(User $user): string
    {
        return UsersFacade::getEmailVerifyUrl($user);
    }

    /**
     * Sets a new verification code on a user, and returns their new Password Reset URL.
     *
     * @param User $user The user that should get the new Password Reset URL
     *
     * @return string The new Password Reset URL.
     * @throws InvalidElementException if the user doesn't validate
     */
    public function getPasswordResetUrl(User $user): string
    {
        return UsersFacade::getPasswordResetUrl($user);
    }

    /**
     * Removes credentials for a user.
     *
     * @param User $user The user that should have credentials removed.
     *
     * @throws InvalidElementException
     * @since 4.0.0
     */
    public function removeCredentials(User $user): void
    {
        UsersFacade::removeCredentials($user);
    }

    /**
     * Crops and saves a user’s photo.
     *
     * @param User $user the user.
     * @param string $fileLocation the local image path on server
     * @param string|null $filename name of the file to use, defaults to filename of `$fileLocation`
     * @param string|null $mimeType the default MIME type to use, if it can’t be determined based on the server path
     *
     * @throws ImageException if the file provided is not a manipulatable image
     * @throws VolumeException if the user photo volume is not provided or is invalid
     */
    public function saveUserPhoto(
        string $fileLocation,
        User $user,
        ?string $filename = null,
        ?string $mimeType = null,
    ): void {
        UsersFacade::saveUserPhoto(
            $fileLocation,
            $user,
            $filename,
            $mimeType,
        );
    }

    /**
     * Updates the location of a user’s photo.
     *
     * @param User $user
     *
     * @since 3.5.14
     */
    public function relocateUserPhoto(User $user): void
    {
        UsersFacade::relocateUserPhoto($user);
    }

    /**
     * Deletes a user’s photo.
     *
     * @param User $user The user
     *
     * @return bool Whether the user’s photo was deleted successfully
     */
    public function deleteUserPhoto(User $user): bool
    {
        return UsersFacade::deleteUserPhoto($user);
    }

    /**
     * Handles a valid login for a user.
     *
     * @param User $user The user
     */
    public function handleValidLogin(User $user): void
    {
        UsersFacade::handleValidLogin($user);
    }

    /**
     * Handles an invalid login for a user.
     *
     * @param User $user The user
     */
    public function handleInvalidLogin(User $user): void
    {
        UsersFacade::handleInvalidLogin($user);
    }

    /**
     * Activates a user, bypassing email verification.
     *
     * @param User $user The user.
     *
     * @throws InvalidElementException
     */
    public function activateUser(User $user): void
    {
        UsersFacade::activateUser($user);
    }

    /**
     * Deactivates a user.
     *
     * @param User $user The user.
     *
     * @throws Throwable if reasons
     * @throws InvalidElementException
     * @since 4.0.0
     */
    public function deactivateUser(User $user): void
    {
        UsersFacade::deactivateUser($user);
    }

    /**
     * If 'unverifiedEmail' is set on the User, then this method will transfer it to the official email property
     * and clear the unverified one.
     *
     * @param User $user
     *
     * @throws InvalidElementException
     */
    public function verifyEmailForUser(User $user): void
    {
        UsersFacade::verifyEmailForUser($user);
    }

    /**
     * Unlocks a user, bypassing the cooldown phase.
     *
     * @param User $user The user.
     *
     * @throws InvalidElementException
     */
    public function unlockUser(User $user): void
    {
        UsersFacade::unlockUser($user);
    }

    /**
     * Suspends a user.
     *
     * @param User $user The user.
     *
     * @throws InvalidElementException
     */
    public function suspendUser(User $user): void
    {
        UsersFacade::suspendUser($user);
    }

    /**
     * Unsuspends a user.
     *
     * @param User $user The user.
     *
     * @throws InvalidElementException
     */
    public function unsuspendUser(User $user): void
    {
        UsersFacade::unsuspendUser($user);
    }

    /**
     * Shuns a message for a user.
     *
     * @param int $userId The user’s ID.
     * @param string $message The message to be shunned.
     * @param DateTime|null $expiryDate When the message should be un-shunned. Defaults to `null` (never un-shun).
     */
    public function shunMessageForUser(int $userId, string $message, ?DateTime $expiryDate = null): void
    {
        UsersFacade::shunMessageForUser($userId, $message, $expiryDate);
    }

    /**
     * Un-shuns a message for a user.
     *
     * @param int $userId The user’s ID.
     * @param string $message The message to un-shun.
     */
    public function unshunMessageForUser(int $userId, string $message): void
    {
        UsersFacade::unshunMessageForUser($userId, $message);
    }

    /**
     * Returns whether a message is shunned for a user.
     *
     * @param int $userId The user’s ID.
     * @param string $message The message to check.
     *
     * @return bool Whether the user has shunned the message.
     */
    public function hasUserShunnedMessage(int $userId, string $message): bool
    {
        return UsersFacade::hasUserShunnedMessage($userId, $message);
    }

    /**
     * Sets a new verification code on the user’s record.
     *
     * @param User $user The user.
     *
     * @return string The user’s brand new verification code.
     * @throws InvalidElementException if the user doesn't validate
     */
    public function setVerificationCodeOnUser(User $user): string
    {
        return UsersFacade::setVerificationCodeOnUser($user);
    }

    /**
     * Deletes any pending users that have shown zero sense of urgency and are
     * just taking up space.
     *
     * This method will check the <config5:purgePendingUsersDuration> config
     * setting, and if it is set to a valid duration, it will delete any user
     * accounts that were created that duration ago, and have still not
     * activated their account.
     *
     */
    public function purgeExpiredPendingUsers(): void
    {
        UsersFacade::purgeExpiredPendingUsers();
    }

    /**
     * Assigns a user to a given list of user groups.
     *
     * @param int $userId The user’s ID
     * @param int[] $groupIds The groups’ IDs. Pass an empty array to remove a user from all groups.
     *
     * @return bool Whether the users were successfully assigned to the groups.
     */
    public function assignUserToGroups(int $userId, array $groupIds): bool
    {
        return UsersFacade::assignUserToGroups($userId, $groupIds);
    }

    /**
     * Returns the default user groups that the given user should belong to.
     *
     * @param User $user
     *
     * @return UserGroup[]
     * @since 4.5.4
     */
    public function getDefaultUserGroups(User $user): array
    {
        return UsersFacade::getDefaultUserGroups($user);
    }

    /**
     * Assigns a user to the default user group(s).
     *
     * This method is called toward the end of a public registration request.
     *
     * @param User $user The user that was just registered.
     *
     * @return bool Whether the user was assigned to the default group.
     */
    public function assignUserToDefaultGroup(User $user): bool
    {
        return UsersFacade::assignUserToDefaultGroup($user);
    }

    /**
     * Handle user field layout changes.
     *
     * @param ConfigEvent $event
     */
    public function handleChangedUserFieldLayout(ConfigEvent $event): void
    {
        UsersFacade::handleChangedUserFieldLayout($event);
    }

    /**
     * Save the user field layout
     *
     * @param FieldLayout $layout
     * @param bool $runValidation Whether the layout should be validated
     *
     * @return bool
     */
    public function saveLayout(FieldLayout $layout, bool $runValidation = true): bool
    {
        return UsersFacade::saveLayout($layout, $runValidation);
    }

    /**
     * Returns whether a user is allowed to impersonate another user.
     *
     * @param User $impersonator
     * @param User $impersonatee
     *
     * @return bool
     * @since 3.2.0
     */
    public function canImpersonate(User $impersonator, User $impersonatee): bool
    {
        return UsersFacade::canImpersonate($impersonator, $impersonatee);
    }

    /**
     * Returns whether the user can suspend the given user
     *
     * @param User $suspender
     * @param User $suspendee
     *
     * @return bool
     * @since 3.7.32
     */
    public function canSuspend(User $suspender, User $suspendee): bool
    {
        return UsersFacade::canSuspend($suspender, $suspendee);
    }

    /**
     * @deprecated in 4.0.5. Unused fields will be pruned automatically as field layouts are resaved.
     */
    public function pruneDeletedField(): void
    {
    }

    /**
     * Returns the maximum number of users the system can have, for the given Craft edition.
     *
     * @param Edition $edition
     *
     * @return int|null
     * @since 5.5.0
     */
    final public function getMaxUsers(Edition $edition): ?int
    {
        return UsersFacade::getMaxUsers($edition);
    }

    /**
     * Returns whether new users can be added to the system.
     *
     * @return bool
     * @since 5.0.0
     */
    final public function canCreateUsers(): bool
    {
        return UsersFacade::canCreateUsers();
    }

    public static function registerEvents(): void
    {
        Event::listen(UserPhotoSaving::class, function(UserPhotoSaving $event) {
            if (Craft::$app->getUsers()->hasEventHandlers(self::EVENT_BEFORE_SAVE_USER_PHOTO)) {
                $yiiEvent = new UserPhotoEvent([
                    'user' => $event->user,
                    'photoId' => $event->photoId,
                ]);

                Craft::$app->getUsers()->trigger(self::EVENT_BEFORE_SAVE_USER_PHOTO, $yiiEvent);

                $event->photoId = $yiiEvent->photoId;
            }
        });

        Event::listen(UserPhotoSaved::class, function(UserPhotoSaved $event) {
            if (Craft::$app->getUsers()->hasEventHandlers(self::EVENT_AFTER_SAVE_USER_PHOTO)) {
                $yiiEvent = new UserPhotoEvent([
                    'user' => $event->user,
                    'photoId' => $event->photoId,
                ]);

                Craft::$app->getUsers()->trigger(self::EVENT_AFTER_SAVE_USER_PHOTO, $yiiEvent);
            }
        });

        Event::listen(UserPhotoDeleting::class, function(UserPhotoDeleting $event) {
            if (Craft::$app->getUsers()->hasEventHandlers(self::EVENT_BEFORE_DELETE_USER_PHOTO)) {
                $yiiEvent = new UserPhotoEvent([
                    'user' => $event->user,
                    'photoId' => $event->photoId,
                ]);

                Craft::$app->getUsers()->trigger(self::EVENT_BEFORE_DELETE_USER_PHOTO, $yiiEvent);
            }
        });

        Event::listen(UserPhotoDeleted::class, function(UserPhotoDeleted $event) {
            if (Craft::$app->getUsers()->hasEventHandlers(self::EVENT_AFTER_DELETE_USER_PHOTO)) {
                $yiiEvent = new UserPhotoEvent([
                    'user' => $event->user,
                    'photoId' => $event->photoId,
                ]);

                Craft::$app->getUsers()->trigger(self::EVENT_AFTER_DELETE_USER_PHOTO, $yiiEvent);
            }
        });

        foreach ([
             UserUnlocking::class => self::EVENT_BEFORE_UNLOCK_USER,
             UserActivating::class => self::EVENT_BEFORE_ACTIVATE_USER,
             UserDeactivating::class => self::EVENT_BEFORE_DEACTIVATE_USER,
             UserSuspending::class => self::EVENT_BEFORE_SUSPEND_USER,
             UserUnsuspending::class => self::EVENT_BEFORE_UNSUSPEND_USER,
         ] as $new => $old) {
            Event::listen($new, function(\CraftCms\Cms\User\Events\UserEvent $event) use ($old) {
                if (Craft::$app->getUsers()->hasEventHandlers($old)) {
                    $yiiEvent = new UserEvent([
                        'user' => $event->user,
                    ]);

                    Craft::$app->getUsers()->trigger($old, $yiiEvent);

                    /**
                     * @var \CraftCms\Cms\Shared\Concerns\ValidatableEvent $event
                     * @phpstan-ignore-next-line
                     */
                    $event->isValid = $yiiEvent->isValid;
                }
            });
        }

        foreach ([
            UserLocked::class => self::EVENT_AFTER_LOCK_USER,
            UserActivated::class => self::EVENT_AFTER_ACTIVATE_USER,
            UserDeactivated::class => self::EVENT_AFTER_DEACTIVATE_USER,
            UserUnlocked::class => self::EVENT_AFTER_UNLOCK_USER,
            UserSuspended::class => self::EVENT_AFTER_SUSPEND_USER,
            UserUnsuspended::class => self::EVENT_AFTER_UNSUSPEND_USER,
            UserEmailVerifying::class => self::EVENT_BEFORE_VERIFY_EMAIL,
            EmailVerified::class => self::EVENT_AFTER_VERIFY_EMAIL,
        ] as $new => $old) {
            Event::listen($new, function(\CraftCms\Cms\User\Events\UserEvent $event) use ($old) {
                if (Craft::$app->getUsers()->hasEventHandlers($old)) {
                    $yiiEvent = new UserEvent([
                        'user' => $event->user,
                    ]);

                    Craft::$app->getUsers()->trigger($old, $yiiEvent);
                }
            });
        }

        Event::listen(UserGroupsAssigning::class, function(UserGroupsAssigning $event) {
            // Fire a 'beforeAssignUserToGroups' event
            if (Craft::$app->getUsers()->hasEventHandlers(self::EVENT_BEFORE_ASSIGN_USER_TO_GROUPS)) {
                $yiiEvent = new UserGroupsAssignEvent([
                    'userId' => $event->userId,
                    'groupIds' => $event->groupIds,
                    'removedGroupIds' => $event->removedGroupIds,
                    'newGroupIds' => $event->newGroupIds,
                ]);

                Craft::$app->getUsers()->trigger(self::EVENT_BEFORE_ASSIGN_USER_TO_GROUPS, $yiiEvent);

                $event->isValid = $yiiEvent->isValid;
                $event->removedGroupIds = $yiiEvent->removedGroupIds;
                $event->newGroupIds = $yiiEvent->newGroupIds;
            }
        });

        Event::listen(UserAssignedToGroups::class, function(UserAssignedToGroups $event) {
            // Fire a 'beforeAssignUserToGroups' event
            if (Craft::$app->getUsers()->hasEventHandlers(self::EVENT_AFTER_ASSIGN_USER_TO_GROUPS)) {
                $yiiEvent = new UserGroupsAssignEvent([
                    'userId' => $event->userId,
                    'groupIds' => $event->groupIds,
                    'removedGroupIds' => $event->removedGroupIds,
                    'newGroupIds' => $event->newGroupIds,
                ]);

                Craft::$app->getUsers()->trigger(self::EVENT_AFTER_ASSIGN_USER_TO_GROUPS, $yiiEvent);
            }
        });

        Event::listen(DefaultUserGroupsResolving::class, function(DefaultUserGroupsResolving $event) {
            if (Craft::$app->getUsers()->hasEventHandlers(self::EVENT_DEFINE_DEFAULT_USER_GROUPS)) {
                $yiiEvent = new DefineUserGroupsEvent([
                    'user' => $event->user,
                    'userGroups' => $event->userGroups,
                ]);

                Craft::$app->getUsers()->trigger(self::EVENT_DEFINE_DEFAULT_USER_GROUPS, $yiiEvent);

                $event->userGroups = $yiiEvent->userGroups;
            }
        });

        Event::listen(UserDefaultGroupsAssigning::class, function(UserDefaultGroupsAssigning $event) {
            if (Craft::$app->getUsers()->hasEventHandlers(self::EVENT_BEFORE_ASSIGN_USER_TO_DEFAULT_GROUP)) {
                $yiiEvent = new UserAssignGroupEvent([
                    'user' => $event->user,
                    'userGroups' => $event->userGroups,
                ]);

                Craft::$app->getUsers()->trigger(self::EVENT_BEFORE_ASSIGN_USER_TO_DEFAULT_GROUP, $yiiEvent);

                $event->isValid = $yiiEvent->isValid;
            }
        });

        Event::listen(UserAssignedToDefaultGroups::class, function(UserAssignedToDefaultGroups $event) {
            if (Craft::$app->getUsers()->hasEventHandlers(self::EVENT_AFTER_ASSIGN_USER_TO_DEFAULT_GROUP)) {
                $yiiEvent = new UserAssignGroupEvent([
                    'user' => $event->user,
                    'userGroups' => $event->userGroups,
                ]);

                Craft::$app->getUsers()->trigger(self::EVENT_AFTER_ASSIGN_USER_TO_DEFAULT_GROUP, $yiiEvent);
            }
        });
    }
}
