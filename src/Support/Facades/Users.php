<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static \CraftCms\Cms\User\Elements\User ensureUserByEmail(string $email)
 * @method static \CraftCms\Cms\User\Elements\User|null getUserById(int $userId)
 * @method static \CraftCms\Cms\User\Elements\User|null getUserByUsernameOrEmail(string $usernameOrEmail)
 * @method static \CraftCms\Cms\User\Elements\User|null getUserByUid(string $uid)
 * @method static array getUserPreferences(int $userId)
 * @method static void saveUserPreferences(\CraftCms\Cms\User\Elements\User $user, array $preferences)
 * @method static mixed getUserPreference(int $userId, string $key, mixed $default = null)
 * @method static bool sendPasswordResetEmail(\CraftCms\Cms\User\Elements\User $user)
 * @method static string getActivationUrl(\CraftCms\Cms\User\Elements\User $user)
 * @method static string getEmailVerifyUrl(\CraftCms\Cms\User\Elements\User $user, string|null $token = null)
 * @method static string getPasswordResetUrl(\CraftCms\Cms\User\Elements\User $user, string|null $token = null)
 * @method static void removeCredentials(\CraftCms\Cms\User\Elements\User $user)
 * @method static void saveUserPhoto(string $fileLocation, \CraftCms\Cms\User\Elements\User $user, string|null $filename = null, string|null $mimeType = null)
 * @method static void relocateUserPhoto(\CraftCms\Cms\User\Elements\User $user)
 * @method static bool deleteUserPhoto(\CraftCms\Cms\User\Elements\User $user)
 * @method static void handleValidLogin(\CraftCms\Cms\User\Elements\User $user)
 * @method static void handleInvalidLogin(\CraftCms\Cms\User\Elements\User $user)
 * @method static void activateUser(\CraftCms\Cms\User\Elements\User $user)
 * @method static void deactivateUser(\CraftCms\Cms\User\Elements\User $user)
 * @method static void verifyEmailForUser(\CraftCms\Cms\User\Elements\User $user)
 * @method static void unlockUser(\CraftCms\Cms\User\Elements\User $user)
 * @method static void suspendUser(\CraftCms\Cms\User\Elements\User $user)
 * @method static void unsuspendUser(\CraftCms\Cms\User\Elements\User $user)
 * @method static void shunMessageForUser(int $userId, string $message, \DateTime|null $expiryDate = null)
 * @method static void unshunMessageForUser(int $userId, string $message)
 * @method static bool hasUserShunnedMessage(int $userId, string $message)
 * @method static string setVerificationCodeOnUser(\CraftCms\Cms\User\Elements\User $user)
 * @method static void purgeExpiredPendingUsers()
 * @method static bool assignUserToGroups(int $userId, int[] $groupIds)
 * @method static \CraftCms\Cms\User\Data\UserGroup[] getDefaultUserGroups(\CraftCms\Cms\User\Elements\User $user)
 * @method static bool assignUserToDefaultGroup(\CraftCms\Cms\User\Elements\User $user)
 * @method static void handleChangedUserFieldLayout(\CraftCms\Cms\ProjectConfig\Events\ConfigEvent $event)
 * @method static bool saveLayout(\CraftCms\Cms\FieldLayout\FieldLayout $layout, bool $runValidation = true)
 * @method static bool canImpersonate(\CraftCms\Cms\User\Elements\User $impersonator, \CraftCms\Cms\User\Elements\User $impersonatee)
 * @method static bool canSuspend(\CraftCms\Cms\User\Elements\User $suspender, \CraftCms\Cms\User\Elements\User $suspendee)
 * @method static int|null getMaxUsers(\CraftCms\Cms\Edition $edition)
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
