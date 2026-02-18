<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use CraftCms\Cms\Edition;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\ProjectConfig\Events\ConfigEvent;
use CraftCms\Cms\User\Data\UserGroup;
use CraftCms\Cms\User\Elements\User;
use DateTime;
use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static User ensureUserByEmail(string $email)
 * @method static User|null getUserById(int $userId)
 * @method static User|null getUserByUsernameOrEmail(string $usernameOrEmail)
 * @method static User|null getUserByUid(string $uid)
 * @method static array getUserPreferences(int $userId)
 * @method static void saveUserPreferences(User $user, array $preferences)
 * @method static mixed getUserPreference(int $userId, string $key, mixed $default = null)
 * @method static bool sendPasswordResetEmail(User $user)
 * @method static string getActivationUrl(User $user)
 * @method static string getEmailVerifyUrl(User $user, string|null $token = null)
 * @method static string getPasswordResetUrl(User $user, string|null $token = null)
 * @method static void removeCredentials(User $user)
 * @method static void saveUserPhoto(string $fileLocation, User $user, string|null $filename = null, string|null $mimeType = null)
 * @method static void relocateUserPhoto(User $user)
 * @method static bool deleteUserPhoto(User $user)
 * @method static void handleValidLogin(User $user)
 * @method static void handleInvalidLogin(User $user)
 * @method static void activateUser(User $user)
 * @method static void deactivateUser(User $user)
 * @method static void verifyEmailForUser(User $user)
 * @method static void unlockUser(User $user)
 * @method static void suspendUser(User $user)
 * @method static void unsuspendUser(User $user)
 * @method static void shunMessageForUser(int $userId, string $message, DateTime|null $expiryDate = null)
 * @method static void unshunMessageForUser(int $userId, string $message)
 * @method static bool hasUserShunnedMessage(int $userId, string $message)
 * @method static string setVerificationCodeOnUser(User $user)
 * @method static void purgeExpiredPendingUsers()
 * @method static bool assignUserToGroups(int $userId, int[] $groupIds)
 * @method static UserGroup[] getDefaultUserGroups(User $user)
 * @method static bool assignUserToDefaultGroup(User $user)
 * @method static void handleChangedUserFieldLayout(ConfigEvent $event)
 * @method static bool saveLayout(FieldLayout $layout, bool $runValidation = true)
 * @method static bool canImpersonate(User $impersonator, User $impersonatee)
 * @method static bool canSuspend(User $suspender, User $suspendee)
 * @method static int|null getMaxUsers(Edition $edition)
 * @method static bool canCreateUsers()
 *
 * @see \CraftCms\Cms\User\Users
 */
final class Users extends Facade
{
    #[Override]
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\User\Users::class;
    }
}
