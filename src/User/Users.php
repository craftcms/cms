<?php

declare(strict_types=1);

namespace CraftCms\Cms\User;

use Craft;
use craft\errors\ImageException;
use craft\errors\InvalidSubpathException;
use craft\errors\VolumeException;
use craft\helpers\Assets as AssetsHelper;
use craft\helpers\FileHelper;
use craft\helpers\Image;
use craft\helpers\UrlHelper;
use craft\web\Request;
use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Element\Exceptions\InvalidElementException;
use CraftCms\Cms\Element\Queries\UserQuery;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\ProjectConfig\Events\ConfigEvent;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\ProjectConfig\ProjectConfigHelper;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Volumes;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Events\ActivatingUser;
use CraftCms\Cms\User\Events\AssigningUserToDefaultGroups;
use CraftCms\Cms\User\Events\AssigningUserToGroups;
use CraftCms\Cms\User\Events\DeactivatingUser;
use CraftCms\Cms\User\Events\DefineDefaultUserGroups;
use CraftCms\Cms\User\Events\DeletingUserPhoto;
use CraftCms\Cms\User\Events\SavingUserPhoto;
use CraftCms\Cms\User\Events\SuspendingUser;
use CraftCms\Cms\User\Events\UnlockingUser;
use CraftCms\Cms\User\Events\UnsuspendingUser;
use CraftCms\Cms\User\Events\UserActivated;
use CraftCms\Cms\User\Events\UserAssignedToDefaultGroups;
use CraftCms\Cms\User\Events\UserAssignedToGroups;
use CraftCms\Cms\User\Events\UserDeactivated;
use CraftCms\Cms\User\Events\UserLocked;
use CraftCms\Cms\User\Events\UserPhotoDeleted;
use CraftCms\Cms\User\Events\UserPhotoSaved;
use CraftCms\Cms\User\Events\UserSuspended;
use CraftCms\Cms\User\Events\UserUnlocked;
use CraftCms\Cms\User\Events\UserUnsuspended;
use CraftCms\Cms\User\Models\User as UserModel;
use CraftCms\DependencyAwareCache\Dependency\TagDependency;
use DateTime;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use InvalidArgumentException;
use Throwable;
use Tpetry\QueryExpressions\Function\String\Lower;
use yii\base\Exception;
use yii\base\UserException;

use function CraftCms\Cms\renderObjectTemplate;
use function CraftCms\Cms\t;

#[Singleton]
final class Users
{
    /**
     * @var array Cached user preferences.
     *
     * @see getUserPreferences()
     */
    private array $userPreferences = [];

    /**
     * Returns a user by an email address, creating one if none already exists.
     *
     * @throws InvalidArgumentException if `$email` is invalid
     * @throws Exception if the user couldn’t be saved for some unexpected reason
     */
    public function ensureUserByEmail(string $email): User
    {
        /** @var User|null $user */
        $user = User::find()
            ->email($email)
            ->status([UserQuery::STATUS_CREDENTIALED, User::STATUS_INACTIVE])
            ->first();

        if ($user) {
            return $user;
        }

        $user = new User;
        $user->email = $email;

        if (! $user->validate(['email'])) {
            throw new InvalidArgumentException($user->errors()->first('email'));
        }

        if (! Craft::$app->getElements()->saveElement($user, false)) {
            throw new Exception('Unable to save user: '.implode(', ', $user->getFirstErrors()));
        }

        return $user;
    }

    /**
     * Returns a user by their ID.
     *
     * ```php
     * $user = Craft::$app->users->getUserById($userId);
     * ```
     *
     * @param  int  $userId  The user’s ID.
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
     * @param  string  $usernameOrEmail  The user’s username or email.
     * @return User|null The user with the given username/email, or `null` if a user could not be found.
     */
    public function getUserByUsernameOrEmail(string $usernameOrEmail): ?User
    {
        return User::find()
            ->addSelect([
                'users.password as password',
                'users.passwordResetRequired as passwordResetRequired',
            ])
            ->status(null)
            ->where(function (Builder $query) use ($usernameOrEmail) {
                $query->where(new Lower('username'), mb_strtolower($usernameOrEmail))
                    ->orWhere(new Lower('email'), mb_strtolower($usernameOrEmail));
            })
            ->first();
    }

    /**
     * Returns a user by their UID.
     *
     * ```php
     * $user = Craft::$app->users->getUserByUid($userUid);
     * ```
     *
     * @param  string  $uid  The user’s UID.
     * @return User|null The user with the given UID, or `null` if a user could not be found.
     */
    public function getUserByUid(string $uid): ?User
    {
        return User::find()
            ->uid($uid)
            ->status(null)
            ->first();
    }

    /**
     * Returns a user’s preferences.
     *
     * @param  int  $userId  The user’s ID
     * @return array The user’s preferences
     */
    public function getUserPreferences(int $userId): array
    {
        if (isset($this->userPreferences[$userId])) {
            return $this->userPreferences[$userId];
        }

        $preferences = DB::table(Table::USERPREFERENCES)
            ->where('userId', $userId)
            ->value('preferences');

        if ($preferences) {
            if (is_string($preferences)) {
                $preferences = Json::decode($preferences);
            }
        } else {
            $preferences = [];
        }

        return $this->userPreferences[$userId] = $preferences;
    }

    /**
     * Saves a user’s preferences.
     *
     * @param  User  $user  The user
     * @param  array  $preferences  The user’s new preferences
     */
    public function saveUserPreferences(User $user, array $preferences): void
    {
        // Merge in any other saved preferences
        $preferences += $this->getUserPreferences($user->id);

        DB::table(Table::USERPREFERENCES)
            ->upsert([
                'userId' => $user->id,
                'preferences' => Json::encode($preferences),
            ], ['userId']);

        $this->userPreferences[$user->id] = $preferences;
    }

    /**
     * Returns one of a user’s preferences by its key.
     *
     * @param  int  $userId  The user’s ID
     * @param  string  $key  The preference’s key
     * @param  mixed  $default  The default value, if the preference hasn’t been set
     * @return mixed The user’s preference
     */
    public function getUserPreference(int $userId, string $key, mixed $default = null): mixed
    {
        return Arr::get($this->getUserPreferences($userId), $key, $default);
    }

    /**
     * Sends a password reset email to a user.
     *
     * @param  User  $user  The user to send the forgot password email to.
     * @return bool Whether the email was sent successfully.
     *
     * @throws InvalidElementException if the user doesn't validate
     */
    public function sendPasswordResetEmail(User $user): bool
    {
        return Password::broker('craft')->sendResetLink(['loginName' => $user->email]) === Password::RESET_LINK_SENT;
    }

    /**
     * Sets a new verification code on a user, and returns their activation URL.
     *
     *
     * @throws InvalidElementException if the user doesn't validate
     */
    public function getActivationUrl(User $user): string
    {
        // If the user doesn't have a password yet, use a Password Reset URL
        if (! $user->password) {
            return $this->getPasswordResetUrl($user);
        }

        return $this->getEmailVerifyUrl($user);
    }

    /**
     * Sets a new verification code on a user, and returns their new Email Verification URL.
     *
     * @param  User  $user  The user that should get the new Email Verification URL.
     * @param  string|null  $token  The verification token.
     * @return string The new Email Verification URL.
     *
     * @throws InvalidElementException if the user doesn't validate
     */
    public function getEmailVerifyUrl(User $user, ?string $token = null): string
    {
        $fePath = Cms::config()->getVerifyEmailPath();

        return $this->getUserUrl($user, $fePath, Request::CP_PATH_VERIFY_EMAIL, $token);
    }

    /**
     * Sets a new verification code on a user, and returns their new Password Reset URL.
     *
     * @param  User  $user  The user that should get the new Password Reset URL
     * @param  string|null  $token  The password reset token.
     * @return string The new Password Reset URL.
     *
     * @throws InvalidElementException if the user doesn't validate
     */
    public function getPasswordResetUrl(User $user, ?string $token = null): string
    {
        $fePath = Cms::config()->getSetPasswordPath();

        return $this->getUserUrl($user, $fePath, Request::CP_PATH_SET_PASSWORD, $token);
    }

    /**
     * Removes credentials for a user.
     *
     * @param  User  $user  The user that should have credentials removed.
     *
     * @throws InvalidElementException
     */
    public function removeCredentials(User $user): void
    {
        $userModel = UserModel::findOrFail($user->id);
        $userModel->active = false;
        $userModel->pending = false;
        $userModel->password = null;

        $indexAttributesChanged = $userModel->haveIndexAttributesChanged();

        if (! $userModel->save()) {
            throw new InvalidElementException($user);
        }

        $user->active = false;
        $user->pending = false;
        $user->password = null;

        if ($indexAttributesChanged) {
            $this->invalidateIndexCaches();
        }
    }

    /**
     * Crops and saves a user’s photo.
     *
     * @param  User  $user  the user.
     * @param  string  $fileLocation  the local image path on server
     * @param  string|null  $filename  name of the file to use, defaults to filename of `$fileLocation`
     * @param  string|null  $mimeType  the default MIME type to use, if it can’t be determined based on the server path
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
        $filename = AssetsHelper::prepareAssetName($filename ?? pathinfo($fileLocation, PATHINFO_BASENAME), true, true);

        if (! Image::canManipulateAsImage(pathinfo($fileLocation, PATHINFO_EXTENSION))) {
            throw new ImageException(t('User photo must be an image that Craft can manipulate.'));
        }

        $assetsService = Craft::$app->getAssets();
        $photoId = $user->photoId;

        event($event = new SavingUserPhoto($user, $photoId));

        // If the photo exists, just replace the file.
        if ($event->photoId && ($photo = Craft::$app->getAssets()->getAssetById($event->photoId)) !== null) {
            $assetsService->replaceAssetFile($photo, $fileLocation, $filename, $mimeType);
        } else {
            $volume = $this->userPhotoVolume();
            $folderId = $this->userPhotoFolderId($user, $volume);
            $filename = $assetsService->getNameReplacementInFolder($filename, $folderId);

            $photo = new Asset;
            $photo->setScenario(Asset::SCENARIO_CREATE);
            $photo->tempFilePath = $fileLocation;
            $photo->setFilename($filename);
            $photo->setMimeType(FileHelper::getMimeType($fileLocation, checkExtension: false) ?? $mimeType);
            $photo->newFolderId = $folderId;
            $photo->setVolumeId($volume->id);

            // Save photo.
            $elementsService = Craft::$app->getElements();
            $elementsService->saveElement($photo);

            $user->setPhoto($photo);
            $elementsService->saveElement($user, false);
        }

        event(new UserPhotoSaved($user, $photo->id));
    }

    /**
     * Updates the location of a user’s photo.
     */
    public function relocateUserPhoto(User $user): void
    {
        if (! $user->photoId || ($photo = $user->getPhoto()) === null) {
            return;
        }

        $volume = $this->userPhotoVolume();
        $folderId = $this->userPhotoFolderId($user, $volume);

        if ($photo->folderId === $folderId) {
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
     * @throws VolumeException if no user photo volume is set, or it's set to an invalid volume UID
     */
    private function userPhotoVolume(): Volume
    {
        $uid = app(ProjectConfig::class)->get('users.photoVolumeUid');

        if (! $uid) {
            throw new VolumeException('No user photo volume is set.');
        }

        $volume = Volumes::getVolumeByUid($uid);
        if ($volume === null) {
            throw new VolumeException("Invalid volume UID: $uid");
        }

        return $volume;
    }

    /**
     * Returns the folder that a user’s photo should be stored.
     *
     * @param  Volume  $volume  The user photo volume
     *
     * @throws VolumeException if the user photo volume doesn’t exist
     * @throws InvalidSubpathException if the user photo subpath can’t be resolved
     */
    private function userPhotoFolderId(User $user, Volume $volume): int
    {
        $subpath = (string) app(ProjectConfig::class)->get('users.photoSubpath');

        if ($subpath !== '') {
            try {
                $subpath = renderObjectTemplate($subpath, $user);
            } catch (Throwable) {
                throw new InvalidSubpathException($subpath);
            }
        }

        return Craft::$app->getAssets()->ensureFolderByFullPathAndVolume($subpath, $volume)->id;
    }

    /**
     * Deletes a user’s photo.
     *
     * @param  User  $user  The user
     * @return bool Whether the user’s photo was deleted successfully
     */
    public function deleteUserPhoto(User $user): bool
    {
        $photoId = $user->photoId;

        event(new DeletingUserPhoto($user, $photoId));

        $result = Craft::$app->getElements()->deleteElementById($photoId, Asset::class);

        if ($result) {
            $user->setPhoto();

            event(new UserPhotoDeleted($user, $photoId));
        }

        return $result;
    }

    /**
     * Handles a valid login for a user.
     *
     * @param  User  $user  The user
     */
    public function handleValidLogin(User $user): void
    {
        $now = now('UTC');

        // Update the User record
        $userModel = UserModel::findOrFail($user->id);
        $userModel->lastLoginDate = $now;
        $userModel->invalidLoginWindowStart = null;
        $userModel->invalidLoginCount = null;

        if (Cms::config()->storeUserIps) {
            $userModel->lastLoginAttemptIp = request()->ip();
        }

        $indexAttributesChanged = $userModel->haveIndexAttributesChanged();
        $userModel->save();

        // Update the User model too
        $user->lastLoginDate = $now->toDateTime();
        $user->invalidLoginCount = null;

        if ($indexAttributesChanged) {
            $this->invalidateIndexCaches();
        }
    }

    /**
     * Handles an invalid login for a user.
     *
     * @param  User  $user  The user
     */
    public function handleInvalidLogin(User $user): void
    {
        $userModel = UserModel::findOrFail($user->id);
        $now = now('UTC');

        $userModel->lastInvalidLoginDate = $now;

        if (Cms::config()->storeUserIps) {
            $userModel->lastLoginAttemptIp = request()->ip();
        }

        // Was that one too many?
        $maxInvalidLogins = Cms::config()->maxInvalidLogins;
        $alreadyLocked = $user->locked;

        if ($maxInvalidLogins) {
            if ($this->isUserInsideInvalidLoginWindow($userModel)) {
                $userModel->invalidLoginCount++;

                // Was that one bad password too many?
                if ($userModel->invalidLoginCount >= $maxInvalidLogins) {
                    $userModel->locked = true;
                    $userModel->invalidLoginCount = null;
                    $userModel->invalidLoginWindowStart = null;
                    $userModel->lockoutDate = $now;

                    $user->locked = true;
                    $user->lockoutDate = $now;
                }
            } else {
                // Start the invalid login window and counter
                $userModel->invalidLoginWindowStart = $now;
                $userModel->invalidLoginCount = 1;
            }

            // Update the counter on the user element
            $user->invalidLoginCount = $userModel->invalidLoginCount;
        }

        $indexAttributesChanged = $userModel->haveIndexAttributesChanged();
        $userModel->save();

        // Update the User element too
        $user->lastInvalidLoginDate = $now;

        if (! $alreadyLocked && $user->locked) {
            event(new UserLocked($user));
        }

        if ($indexAttributesChanged) {
            $this->invalidateIndexCaches();
        }
    }

    /**
     * Activates a user, bypassing email verification.
     *
     * @param  User  $user  The user.
     *
     * @throws InvalidElementException
     */
    public function activateUser(User $user): void
    {
        event($event = new ActivatingUser($user));

        if (! $event->isValid) {
            throw new InvalidElementException($user);
        }

        $originalUser = clone $user;
        $user->setScenario(User::SCENARIO_ACTIVATION);
        $user->active = true;
        $user->pending = false;
        $user->locked = false;
        $user->suspended = false;
        $user->invalidLoginCount = null;
        $user->lastInvalidLoginDate = null;
        $user->lockoutDate = null;

        if (! $user->validate()) {
            $user->active = $originalUser->active;
            $user->pending = $originalUser->pending;
            $user->locked = $originalUser->locked;
            $user->suspended = $originalUser->suspended;
            $user->invalidLoginCount = $originalUser->invalidLoginCount;
            $user->lastInvalidLoginDate = $originalUser->lastInvalidLoginDate;
            $user->lockoutDate = $originalUser->lockoutDate;
            throw new InvalidElementException($user);
        }

        DB::beginTransaction();
        try {
            $userModel = UserModel::findOrFail($user->id);
            $userModel->active = true;
            $userModel->pending = false;
            $userModel->locked = false;
            $userModel->suspended = false;
            $userModel->invalidLoginWindowStart = null;
            $userModel->invalidLoginCount = null;
            $userModel->lastInvalidLoginDate = null;
            $userModel->lockoutDate = null;

            $indexAttributesChanged = $userModel->haveIndexAttributesChanged();
            $userModel->save();

            // If they have an unverified email address, now is the time to set it to their primary email address
            $this->verifyEmailForUser($user);

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        event(new UserActivated($user));

        if ($indexAttributesChanged) {
            $this->invalidateIndexCaches();
        }
    }

    /**
     * Deactivates a user.
     *
     * @param  User  $user  The user.
     *
     * @throws Throwable if reasons
     * @throws InvalidElementException
     */
    public function deactivateUser(User $user): void
    {
        event($event = new DeactivatingUser($user));

        if (! $event->isValid) {
            throw new InvalidElementException($user);
        }

        DB::beginTransaction();
        try {
            $userModel = UserModel::findOrFail($user->id);
            $userModel->active = false;
            $userModel->pending = false;
            $userModel->locked = false;
            $userModel->suspended = false;
            $userModel->invalidLoginWindowStart = null;
            $userModel->invalidLoginCount = null;
            $userModel->lastInvalidLoginDate = null;
            $userModel->lockoutDate = null;

            $indexAttributesChanged = $userModel->haveIndexAttributesChanged();
            $userModel->save();

            $user->active = false;
            $user->pending = false;
            $user->locked = false;
            $user->suspended = false;
            $user->invalidLoginCount = null;
            $user->lastInvalidLoginDate = null;
            $user->lockoutDate = null;

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        event(new UserDeactivated($user));

        if ($indexAttributesChanged) {
            $this->invalidateIndexCaches();
        }
    }

    /**
     * If 'unverifiedEmail' is set on the User, then this method will transfer it to the official email property
     * and clear the unverified one.
     *
     *
     * @throws InvalidElementException
     */
    public function verifyEmailForUser(User $user): void
    {
        // Bail if they don't have an unverified email to begin with
        if (! $user->unverifiedEmail) {
            return;
        }

        $userModel = UserModel::findOrFail($user->id);
        $userModel->email = $user->unverifiedEmail;
        $userModel->unverifiedEmail = null;

        if (Cms::config()->useEmailAsUsername) {
            $userModel->username = $user->unverifiedEmail;
        }

        $indexAttributesChanged = $userModel->haveIndexAttributesChanged();

        if (! $userModel->save()) {
            throw new InvalidElementException($user);
        }

        // If the user status is pending, let's activate them.
        if ($userModel->pending) {
            $this->activateUser($user);
        } elseif ($indexAttributesChanged) {
            $this->invalidateIndexCaches();
        }
    }

    /**
     * Unlocks a user, bypassing the cooldown phase.
     *
     * @param  User  $user  The user.
     *
     * @throws InvalidElementException
     */
    public function unlockUser(User $user): void
    {
        event($event = new UnlockingUser($user));

        if (! $event->isValid) {
            throw new InvalidElementException($user);
        }

        DB::beginTransaction();
        try {
            $userModel = UserModel::findOrFail($user->id);
            $userModel->locked = false;
            $userModel->invalidLoginCount = null;
            $userModel->invalidLoginWindowStart = null;
            $userModel->lockoutDate = null;

            $indexAttributesChanged = $userModel->haveIndexAttributesChanged();
            $userModel->save();

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        // Update the User model too
        $user->locked = false;
        $user->invalidLoginCount = null;
        $user->lockoutDate = null;

        event(new UserUnlocked($user));

        if ($indexAttributesChanged) {
            $this->invalidateIndexCaches();
        }
    }

    /**
     * Suspends a user.
     *
     * @param  User  $user  The user.
     *
     * @throws InvalidElementException
     */
    public function suspendUser(User $user): void
    {
        event($event = new SuspendingUser($user));

        if (! $event->isValid) {
            throw new InvalidElementException($user);
        }

        $userModel = UserModel::findOrFail($user->id);
        $userModel->suspended = true;
        $user->suspended = true;

        $indexAttributesChanged = $userModel->haveIndexAttributesChanged();
        $userModel->save();

        // Destroy all sessions for this user
        DB::table(Table::SESSIONS)
            ->where('user_id', $user->id)
            ->delete();

        event(new UserSuspended($user));

        if ($indexAttributesChanged) {
            $this->invalidateIndexCaches();
        }
    }

    /**
     * Unsuspends a user.
     *
     * @param  User  $user  The user.
     *
     * @throws InvalidElementException
     */
    public function unsuspendUser(User $user): void
    {
        event($event = new UnsuspendingUser($user));

        if (! $event->isValid) {
            throw new InvalidElementException($user);
        }

        DB::beginTransaction();

        try {
            $userModel = UserModel::findOrFail($user->id);
            $userModel->suspended = false;

            $indexAttributesChanged = $userModel->haveIndexAttributesChanged();
            $userModel->save();

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            throw $e;
        }

        // Update the User model too
        $user->suspended = false;

        event(new UserUnsuspended($user));

        if ($indexAttributesChanged) {
            $this->invalidateIndexCaches();
        }
    }

    /**
     * Shuns a message for a user.
     *
     * @param  int  $userId  The user’s ID.
     * @param  string  $message  The message to be shunned.
     * @param  DateTime|null  $expiryDate  When the message should be un-shunned. Defaults to `null` (never un-shun).
     */
    public function shunMessageForUser(int $userId, string $message, ?DateTime $expiryDate = null): void
    {
        DB::table(Table::SHUNNEDMESSAGES)
            ->upsert([
                'userId' => $userId,
                'message' => $message,
                'dateCreated' => now(),
                'dateUpdated' => now(),
            ], ['userId', 'message'], [
                'expiryDate' => $expiryDate,
            ]);
    }

    /**
     * Un-shuns a message for a user.
     *
     * @param  int  $userId  The user’s ID.
     * @param  string  $message  The message to un-shun.
     */
    public function unshunMessageForUser(int $userId, string $message): void
    {
        DB::table(Table::SHUNNEDMESSAGES)
            ->where('userId', $userId)
            ->where('message', $message)
            ->delete();
    }

    /**
     * Returns whether a message is shunned for a user.
     *
     * @param  int  $userId  The user’s ID.
     * @param  string  $message  The message to check.
     * @return bool Whether the user has shunned the message.
     */
    public function hasUserShunnedMessage(int $userId, string $message): bool
    {
        return DB::table(Table::SHUNNEDMESSAGES)
            ->where('userId', $userId)
            ->where('message', $message)
            ->where(function (Builder $query) {
                $query->whereNull('expiryDate')
                    ->orWhere('expiryDate', '>', now());
            })
            ->exists();
    }

    /**
     * Sets a new verification code on the user’s record.
     *
     * @param  User  $user  The user.
     * @return string The user’s brand new verification code.
     *
     * @throws InvalidElementException if the user doesn't validate
     */
    public function setVerificationCodeOnUser(User $user): string
    {
        $userModel = UserModel::findOrFail($user->id);

        /** @var \Illuminate\Auth\Passwords\PasswordBroker $broker */
        $broker = Password::broker('craft');
        $token = $broker->createToken($user);

        // Make sure they are set to pending, if not already active
        if (! $userModel->active) {
            $userModel->pending = true;
        }

        $indexAttributesChanged = $userModel->haveIndexAttributesChanged();
        $userModel->save();

        $originalUser = clone $user;
        $user->setScenario(User::SCENARIO_ACTIVATION);
        $user->pending = $userModel->pending;

        if (! $user->validate()) {
            $user->pending = $originalUser->pending;
            throw new InvalidElementException($user);
        }

        if ($indexAttributesChanged) {
            $this->invalidateIndexCaches();
        }

        return $token;
    }

    /**
     * Deletes any pending users that have shown zero sense of urgency and are
     * just taking up space.
     *
     * This method will check the <config5:purgePendingUsersDuration> config
     * setting, and if it is set to a valid duration, it will delete any user
     * accounts that were created that duration ago, and have still not
     * activated their account.
     */
    public function purgeExpiredPendingUsers(): void
    {
        if (Cms::config()->purgePendingUsersDuration === 0) {
            return;
        }

        User::find()
            ->status('pending')
            ->whereNotExists(function (Builder $query) {
                $query->from(Table::PASSWORD_RESET_TOKENS, 'password_reset_tokens')
                    ->whereColumn('password_reset_tokens.email', 'users.email')
                    ->where('password_reset_tokens.created_at', '>=', now()->subSeconds(Cms::config()->purgePendingUsersDuration));
            })
            ->each(function (User $user) {
                try {
                    Craft::$app->getElements()->deleteElement($user);
                    Log::info("Just deleted pending user $user->username ($user->id), because they took too long to activate their account.", [__METHOD__]);
                } catch (UserException $e) {
                    Log::warning($e->getMessage(), [__METHOD__]);
                }
            });
    }

    /**
     * Assigns a user to a given list of user groups.
     *
     * @param  int  $userId  The user’s ID
     * @param  int[]  $groupIds  The groups’ IDs. Pass an empty array to remove a user from all groups.
     * @return bool Whether the users were successfully assigned to the groups.
     */
    public function assignUserToGroups(int $userId, array $groupIds): bool
    {
        // Get the unique, indexed group IDs
        $newGroupIds = collect($groupIds)->filter()->unique()->flip()->all();

        // Get the current groups
        $oldGroups = DB::table(Table::USERGROUPS_USERS)
            ->select(['id', 'groupId'])
            ->where('userId', $userId)
            ->get();

        $removedGroupIds = [];

        foreach ($oldGroups as $oldGroup) {
            // Is the group still selected?
            if (isset($newGroupIds[$oldGroup->groupId])) {
                // Avoid re-inserting it
                unset($newGroupIds[$oldGroup->groupId]);
            } else {
                $removedGroupIds[] = $oldGroup->groupId;
            }
        }

        if (empty($removedGroupIds) && empty($newGroupIds)) {
            // Nothing to do here
            return true;
        }

        $newGroupIds = array_keys($newGroupIds);

        event($event = new AssigningUserToGroups(
            userId: $userId,
            groupIds: $groupIds,
            removedGroupIds: $removedGroupIds,
            newGroupIds: $newGroupIds,
        ));

        if (! $event->isValid) {
            return false;
        }

        $removedGroupIds = $event->removedGroupIds;
        $newGroupIds = $event->newGroupIds;

        // Make sure the event hasn't left us with nothing to do
        if (empty($removedGroupIds) && empty($newGroupIds)) {
            return true;
        }

        DB::beginTransaction();
        try {
            // Add the new groups
            if (! empty($newGroupIds)) {
                DB::table(Table::USERGROUPS_USERS)
                    ->insert(array_map(fn (int $groupId) => [
                        'userId' => $userId,
                        'groupId' => $groupId,
                        'dateCreated' => $now = now('UTC'),
                        'dateUpdated' => $now,
                        'uid' => Str::uuid(),
                    ], $newGroupIds));
            }

            if (! empty($removedGroupIds)) {
                DB::table(Table::USERGROUPS_USERS)
                    ->where('userId', $userId)
                    ->whereIn('groupId', $removedGroupIds)
                    ->delete();
            }

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        event(new UserAssignedToGroups(
            userId: $userId,
            groupIds: $groupIds,
            removedGroupIds: $removedGroupIds,
            newGroupIds: $newGroupIds,
        ));

        $this->invalidateIndexCaches();

        return true;
    }

    /**
     * Returns the default user groups that the given user should belong to.
     *
     *
     * @return \CraftCms\Cms\User\Data\UserGroup[]
     */
    public function getDefaultUserGroups(User $user): array
    {
        $groups = [];
        $uid = app(ProjectConfig::class)->get('users.defaultGroup');
        if ($uid) {
            $group = \CraftCms\Cms\Support\Facades\UserGroups::getGroupByUid($uid);
            if ($group) {
                $groups[] = $group;
            }
        }

        event($event = new DefineDefaultUserGroups($user, $groups));

        return $event->userGroups;
    }

    /**
     * Assigns a user to the default user group(s).
     *
     * This method is called toward the end of a public registration request.
     *
     * @param  User  $user  The user that was just registered.
     * @return bool Whether the user was assigned to the default group.
     */
    public function assignUserToDefaultGroup(User $user): bool
    {
        $groups = $this->getDefaultUserGroups($user);

        if (empty($groups)) {
            return false;
        }

        event($event = new AssigningUserToDefaultGroups($user, $groups));

        if (! $event->isValid) {
            return false;
        }

        $groupIds = Arr::pluck($groups, 'id');

        if (! $this->assignUserToGroups($user->id, $groupIds)) {
            return false;
        }

        event(new UserAssignedToDefaultGroups($user, $groups));

        return true;
    }

    /**
     * Handle user field layout changes.
     */
    public function handleChangedUserFieldLayout(ConfigEvent $event): void
    {
        $data = $event->newValue;

        $fieldsService = app(Fields::class);

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
     * @param  bool  $runValidation  Whether the layout should be validated
     */
    public function saveLayout(FieldLayout $layout, bool $runValidation = true): bool
    {
        if ($runValidation && ! $layout->validate()) {
            Log::info('Field layout not saved due to validation error.', [__METHOD__]);

            return false;
        }

        app(ProjectConfig::class)->set(ProjectConfig::PATH_USER_FIELD_LAYOUTS, [
            $layout->uid => $layout->getConfig(),
        ], 'Save the user field layout');

        return true;
    }

    /**
     * Returns whether a user is allowed to impersonate another user.
     */
    public function canImpersonate(User $impersonator, User $impersonatee): bool
    {
        return $impersonator->can('impersonate', $impersonatee);
    }

    /**
     * Returns whether the user can suspend the given user
     */
    public function canSuspend(User $suspender, User $suspendee): bool
    {
        return $suspender->can('suspend', $suspendee);
    }

    /**
     * Determines if a user is within their invalid login window.
     */
    private function isUserInsideInvalidLoginWindow(UserModel $userModel): bool
    {
        // If we don't even know the last time they logged in, they're good
        if (! $userModel->invalidLoginWindowStart) {
            return false;
        }

        return $userModel->invalidLoginWindowStart
            ->addSeconds(Cms::config()->invalidLoginWindowDuration)
            ->isFuture();
    }

    /**
     * Sets a new verification code on a user, and returns a verification URL.
     *
     * @param  User  $user  The user that should get the new Password Reset URL
     * @param  string  $fePath  The URL or path to use if we end up linking to the front end
     * @param  string  $cpPath  The path to use if we end up linking to the control panel
     * @param  string|null  $token  The password reset token.
     *
     * @throws InvalidElementException if the user doesn't validate
     *
     * @see getEmailVerifyUrl()
     * @see getPasswordResetUrl()
     */
    private function getUserUrl(User $user, string $fePath, string $cpPath, ?string $token = null): string
    {
        $token ??= $this->setVerificationCodeOnUser($user);

        $params = [
            'code' => $token,
            'id' => $user->uid,
        ];

        $isCpRequest = request()->isCpRequest();

        $cp = (
            Edition::get()->value < Edition::Pro->value ||
            ($isCpRequest && $user->can('accessCp')) ||
            (Cms::config()->headlessMode && ! UrlHelper::isAbsoluteUrl($fePath))
        );
        $scheme = UrlHelper::getSchemeForTokenizedUrl($cp);
        $siteId = $isCpRequest ? $user->affiliatedSiteId : null;

        if (! $cp) {
            return UrlHelper::siteUrl($fePath, $params, $scheme, siteId: $siteId);
        }

        // Only use cpUrl() if this is a control panel request, or the base control panel URL has been explicitly set,
        // so UrlHelper won't use HTTP_HOST
        if (Cms::config()->baseCpUrl || $isCpRequest) {
            $url = UrlHelper::cpUrl($cpPath, $params, $scheme);
        } else {
            $path = UrlHelper::prependCpTrigger($cpPath);
            $url = UrlHelper::siteUrl($path, $params, $scheme, siteId: $siteId);
        }

        if (UrlHelper::isRootRelativeUrl($url)) {
            $request = Craft::$app->getRequest();
            if (! app()->runningInConsole()) {
                $url = rtrim($request->getHostInfo().$request->getBaseUrl(), '/').$url;
            }
        }

        return $url;
    }

    /**
     * Returns the maximum number of users the system can have, for the given Craft edition.
     */
    public function getMaxUsers(Edition $edition): ?int
    {
        return match ($edition) {
            Edition::Solo => 1,
            Edition::Team => 5,
            default => null,
        };
    }

    /**
     * Returns whether new users can be added to the system.
     */
    final public function canCreateUsers(): bool
    {
        $maxUsers = $this->getMaxUsers(Edition::get());

        return ! $maxUsers || $maxUsers > User::find()->status(null)->count();
    }

    private function invalidateIndexCaches(): void
    {
        TagDependency::invalidate(sprintf('element-index-query::%s', User::class));
    }
}
