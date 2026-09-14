<?php

declare(strict_types=1);

namespace CraftCms\Cms\User;

use CraftCms\Cms\Asset\AssetsHelper;
use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Asset\Data\VolumeFolder;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Exceptions\ImageException;
use CraftCms\Cms\Asset\Exceptions\VolumeException;
use CraftCms\Cms\Asset\Validation\AssetRules;
use CraftCms\Cms\Auth\Enums\CpAuthPath;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Element\ElementCaches;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\Exceptions\InvalidElementException;
use CraftCms\Cms\Element\Queries\UserQuery;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\Filesystem\Exceptions\InvalidSubpathException;
use CraftCms\Cms\Image\ImageHelper;
use CraftCms\Cms\ProjectConfig\Events\ConfigEvent;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\ProjectConfig\ProjectConfigHelper;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Assets as AssetsService;
use CraftCms\Cms\Support\Facades\Folders;
use CraftCms\Cms\Support\Facades\UserGroups;
use CraftCms\Cms\Support\Facades\Volumes;
use CraftCms\Cms\Support\File;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\User\Contracts\CraftUser;
use CraftCms\Cms\User\Data\UserGroup;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Events\DefaultUserGroupsResolving;
use CraftCms\Cms\User\Events\UserActivated;
use CraftCms\Cms\User\Events\UserActivating;
use CraftCms\Cms\User\Events\UserAssignedToDefaultGroups;
use CraftCms\Cms\User\Events\UserAssignedToGroups;
use CraftCms\Cms\User\Events\UserDeactivated;
use CraftCms\Cms\User\Events\UserDeactivating;
use CraftCms\Cms\User\Events\UserDefaultGroupsAssigning;
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
use CraftCms\Cms\User\Notifications\ActivationNotification;
use CraftCms\Cms\User\Validation\UserRules;
use CraftCms\DependencyAwareCache\Dependency\TagDependency;
use DateTimeInterface;
use Exception;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use InvalidArgumentException;
use Throwable;
use Tpetry\QueryExpressions\Function\String\Lower;

use function CraftCms\Cms\renderObjectTemplate;
use function CraftCms\Cms\t;

#[Scoped]
class Users
{
    public function __construct(
        private readonly Elements $elements,
        private readonly ElementCaches $elementCaches,
    ) {}

    /**
     * @var array<int, array<string, mixed>> Cached user preferences.
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

        if (! $this->elements->saveElement($user, false)) {
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
        return $this->elements->getElementById($userId, User::class);
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
     * @return array<string, mixed> The user’s preferences
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
     * @param  CraftUser  $user  The user
     * @param  array<string, mixed>  $preferences  The user’s new preferences
     */
    public function saveUserPreferences(CraftUser $user, array $preferences): void
    {
        $userId = $user->getCraftUserId();

        if (! $userId) {
            throw new InvalidArgumentException('Cannot save preferences for a user without an ID.');
        }

        // Merge in any other saved preferences
        $preferences += $this->getUserPreferences($userId);

        DB::table(Table::USERPREFERENCES)
            ->upsert([
                'userId' => $userId,
                'preferences' => Json::encode($preferences),
            ], ['userId']);

        $this->userPreferences[$userId] = $preferences;
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
        return Password::broker()->sendResetLink(['email' => $user->email]) === Password::RESET_LINK_SENT;
    }

    /**
     * Sends a new account activation email to a user.
     *
     * @throws InvalidElementException if the user doesn't validate
     */
    public function sendActivationEmail(User $user, ?string $recipient = null): bool
    {
        $user->notify(new ActivationNotification($this->setVerificationCodeOnUser($user), $recipient));

        return true;
    }

    /**
     * Sends a new email verification email to a user.
     *
     * @throws InvalidElementException if the user doesn't validate
     */
    public function sendNewEmailVerifyEmail(User $user): bool
    {
        $user->sendEmailVerificationNotification();

        return true;
    }

    /**
     * Sets a new verification code on a user, and returns their activation URL.
     *
     *
     * @throws InvalidElementException if the user doesn't validate
     */
    public function getActivationUrl(User $user, ?string $token = null): string
    {
        // If the user doesn't have a password yet, use a Password Reset URL
        if (! $user->getHasPassword()) {
            return $this->getPasswordResetUrl($user, $token);
        }

        return $this->getEmailVerifyUrl($user, $token);
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

        return $this->getUserUrl($user, $fePath, CpAuthPath::VerifyEmail->value, $token);
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

        return $this->getUserUrl($user, $fePath, CpAuthPath::SetPassword->value, $token);
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

        if (
            ! ImageHelper::canManipulateAsImage(pathinfo($fileLocation, PATHINFO_EXTENSION)) ||
            ! ImageHelper::canManipulateAsImage(pathinfo($filename, PATHINFO_EXTENSION))
        ) {
            throw new ImageException(t('User photo must be an image that Craft can manipulate.'));
        }

        $photoId = $user->photoId;

        event($event = new UserPhotoSaving($user, $photoId));

        // If the photo exists, just replace the file.
        if ($event->photoId && ($photo = AssetsService::getAssetById($event->photoId)) !== null) {
            AssetsService::replaceAssetFile($photo, $fileLocation, $filename, $mimeType);
        } else {
            $folder = $this->userPhotoFolder($user);
            $volume = $folder->getVolume();
            $folderId = $folder->id;
            $filename = AssetsService::getNameReplacementInFolder($filename, $folderId);

            $photo = new Asset;
            $photo->ruleset->useScenario(AssetRules::SCENARIO_CREATE);
            $photo->tempFilePath = $fileLocation;
            $photo->setFilename($filename);
            $photo->setMimeType(File::getMimeType($fileLocation, checkExtension: false) ?? $mimeType);
            $photo->newFolderId = $folderId;
            $photo->setVolumeId($volume->id);

            // Save photo.
            $this->elements->saveElement($photo);

            $user->setPhoto($photo);
            $this->elements->saveElement($user, false);
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

        $folderId = $this->userPhotoFolder($user)->id;

        if ($photo->folderId === $folderId) {
            return;
        }

        $photo->ruleset->useScenario(AssetRules::SCENARIO_MOVE);
        $photo->avoidFilenameConflicts = true;
        $photo->newFolderId = $folderId;
        $this->elements->saveElement($photo);
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
     * @throws VolumeException if the user photo volume doesn’t exist
     * @throws InvalidSubpathException if the user photo subpath can’t be resolved
     */
    public function userPhotoFolder(User $user): VolumeFolder
    {
        $volume = $this->userPhotoVolume();
        $subpath = (string) app(ProjectConfig::class)->get('users.photoSubpath');

        if ($subpath !== '') {
            try {
                $subpath = renderObjectTemplate($subpath, $user);
            } catch (Throwable) {
                throw new InvalidSubpathException($subpath);
            }
        }

        if (array_intersect(explode('/', str_replace('\\', '/', $subpath)), ['.', '..'])) {
            throw new InvalidSubpathException($subpath);
        }

        return Folders::ensureFolderByFullPathAndVolume($subpath, $volume, justRecord: false);
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

        event(new UserPhotoDeleting($user, $photoId));

        $result = $this->elements->deleteElementById($photoId, Asset::class);

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
        $changes = [
            'lastLoginDate' => now('UTC')->toDateTime(),
            'invalidLoginWindowStart' => null,
            'invalidLoginCount' => null,
        ];

        if (Cms::config()->storeUserIps) {
            $changes['lastLoginAttemptIp'] = request()->ip();
        }

        $indexAttributesChanged = $this->saveUserChanges($user, UserModel::findOrFail($user->id), $changes);
        $this->applyUserChanges($user, $changes);

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
        $changes = ['lastInvalidLoginDate' => $now];

        if (Cms::config()->storeUserIps) {
            $changes['lastLoginAttemptIp'] = request()->ip();
        }

        $maxInvalidLogins = Cms::config()->maxInvalidLogins;
        $alreadyLocked = $user->locked;

        if ($maxInvalidLogins) {
            if ($this->isUserInsideInvalidLoginWindow($userModel)) {
                $changes['invalidLoginCount'] = $userModel->invalidLoginCount + 1;

                if ($changes['invalidLoginCount'] >= $maxInvalidLogins) {
                    $changes += [
                        'locked' => true,
                        'invalidLoginWindowStart' => null,
                        'lockoutDate' => $now,
                    ];
                    $changes['invalidLoginCount'] = null;
                }
            } else {
                $changes['invalidLoginWindowStart'] = $now;
                $changes['invalidLoginCount'] = 1;
            }
        }

        $indexAttributesChanged = $this->saveUserChanges($user, $userModel, $changes);
        $this->applyUserChanges($user, $changes);

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
        event($event = new UserActivating($user));

        if (! $event->isValid) {
            throw new InvalidElementException($user);
        }

        $changes = [
            'active' => true,
            'pending' => false,
            'locked' => false,
            'suspended' => false,
            'invalidLoginWindowStart' => null,
            'invalidLoginCount' => null,
            'lastInvalidLoginDate' => null,
            'lockoutDate' => null,
        ];
        $this->validateUserChanges($user, $changes);
        $changes += $this->emailVerificationChanges($user);

        $indexAttributesChanged = DB::transaction(fn () => $this->saveUserChanges($user, UserModel::findOrFail($user->id), $changes));
        $this->applyUserChanges($user, $changes);

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
        event($event = new UserDeactivating($user));

        if (! $event->isValid) {
            throw new InvalidElementException($user);
        }

        $changes = [
            'active' => false,
            'pending' => false,
            'locked' => false,
            'suspended' => false,
            'invalidLoginWindowStart' => null,
            'invalidLoginCount' => null,
            'lastInvalidLoginDate' => null,
            'lockoutDate' => null,
        ];
        $indexAttributesChanged = DB::transaction(fn () => $this->saveUserChanges($user, UserModel::findOrFail($user->id), $changes));
        $this->applyUserChanges($user, $changes);

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
        $userModel = UserModel::findOrFail($user->id);
        $changes = $this->emailVerificationChanges($user);
        $indexAttributesChanged = $this->saveUserChanges($user, $userModel, $changes);
        $this->applyUserChanges($user, $changes);

        // If the user status is pending, let's activate them.
        if ($userModel->pending) {
            $this->activateUser($user);
        } elseif ($indexAttributesChanged) {
            $this->invalidateIndexCaches();
        }
    }

    public function unverifyEmailForUser(User $user): void
    {
        if ($user->unverifiedEmail) {
            return;
        }

        $changes = ['unverifiedEmail' => $user->email];
        $this->saveUserChanges($user, UserModel::findOrFail($user->id), $changes);
        $this->applyUserChanges($user, $changes);
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
        event($event = new UserUnlocking($user));

        if (! $event->isValid) {
            throw new InvalidElementException($user);
        }

        $changes = [
            'locked' => false,
            'invalidLoginCount' => null,
            'invalidLoginWindowStart' => null,
            'lockoutDate' => null,
        ];
        $indexAttributesChanged = DB::transaction(fn () => $this->saveUserChanges($user, UserModel::findOrFail($user->id), $changes));
        $this->applyUserChanges($user, $changes);

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
        event($event = new UserSuspending($user));

        if (! $event->isValid) {
            throw new InvalidElementException($user);
        }

        $changes = ['suspended' => true];
        $indexAttributesChanged = $this->saveUserChanges($user, UserModel::findOrFail($user->id), $changes);
        $this->applyUserChanges($user, $changes);

        // Destroy all sessions for this user
        DB::table(Table::SESSIONS)
            ->where('user_id', $user->id)
            ->delete();

        event(new UserSuspended($user));

        if ($indexAttributesChanged) {
            $this->invalidateIndexCaches();
        }
    }

    /** @param array<string, mixed> $changes */
    private function validateUserChanges(User $user, array $changes): void
    {
        $original = [];
        foreach (array_keys(Arr::except($changes, ['invalidLoginWindowStart'])) as $attribute) {
            $original[$attribute] = $user->$attribute;
        }

        $user->ruleset->useScenario(UserRules::SCENARIO_ACTIVATION);
        $this->applyUserChanges($user, $changes);

        try {
            if (! $user->validate()) {
                throw new InvalidElementException($user);
            }
        } finally {
            $this->applyUserChanges($user, $original);
        }
    }

    /** @param array<string, mixed> $changes */
    private function saveUserChanges(User $user, UserModel $model, array $changes): bool
    {
        $model->forceFill($changes);
        $indexAttributesChanged = $model->haveIndexAttributesChanged();

        if (! $model->save()) {
            $user->errors()->add('user', t('Couldn’t save user.'));
            throw new InvalidElementException($user);
        }

        return $indexAttributesChanged;
    }

    /** @param array<string, mixed> $changes */
    private function applyUserChanges(User $user, array $changes): void
    {
        foreach (Arr::except($changes, ['invalidLoginWindowStart']) as $attribute => $value) {
            $user->$attribute = $value;
        }
    }

    /** @return array<string, mixed> */
    private function emailVerificationChanges(User $user): array
    {
        if (! $user->unverifiedEmail) {
            return [];
        }

        $changes = ['email' => $user->unverifiedEmail, 'unverifiedEmail' => null];
        if (Cms::config()->useEmailAsUsername) {
            $changes['username'] = $user->unverifiedEmail;
        }

        return $changes;
    }

    public function invalidateUserSessions(User $user): void
    {
        DB::transaction(function () use ($user) {
            $userModel = UserModel::findOrFail($user->id);
            $userModel->setRememberToken(Str::random(60));
            $userModel->save();

            DB::table(Table::SESSIONS)
                ->where('user_id', $user->id)
                ->delete();
        });
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
        event($event = new UserUnsuspending($user));

        if (! $event->isValid) {
            throw new InvalidElementException($user);
        }

        $changes = ['suspended' => false];
        $indexAttributesChanged = DB::transaction(fn () => $this->saveUserChanges($user, UserModel::findOrFail($user->id), $changes));
        $this->applyUserChanges($user, $changes);

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
     * @param  DateTimeInterface|null  $expiryDate  When the message should be un-shunned. Defaults to `null` (never un-shun).
     */
    public function shunMessageForUser(int $userId, string $message, ?DateTimeInterface $expiryDate = null): void
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
        $changes = ['pending' => $userModel->active ? $userModel->pending : true];
        $this->validateUserChanges($user, $changes);

        /** @var PasswordBroker $broker */
        $broker = Password::broker();
        $token = $broker->createToken($user);

        $indexAttributesChanged = $this->saveUserChanges($user, $userModel, $changes);
        $this->applyUserChanges($user, $changes);

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
            ->cursor()
            ->each(function (User $user) {
                try {
                    $this->elements->deleteElement($user);
                    Log::info("Just deleted pending user $user->username ($user->id), because they took too long to activate their account.", [__METHOD__]);
                } catch (Exception $e) {
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

        event($event = new UserGroupsAssigning(
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
     * @return UserGroup[]
     */
    public function getDefaultUserGroups(User $user): array
    {
        $groups = [];
        $uid = app(ProjectConfig::class)->get('users.defaultGroup');
        if ($uid) {
            $group = UserGroups::getGroupByUid($uid);
            if ($group) {
                $groups[] = $group;
            }
        }

        event($event = new DefaultUserGroupsResolving($user, $groups));

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

        event($event = new UserDefaultGroupsAssigning($user, $groups));

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
        $this->elementCaches->invalidateForElementType(User::class);
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
    public function canImpersonate(CraftUser $impersonator, User $impersonatee): bool
    {
        return $impersonator->can('impersonate', $impersonatee);
    }

    /**
     * Returns whether the user can suspend the given user
     */
    public function canSuspend(CraftUser $suspender, User $suspendee): bool
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
            'uid' => $user->uid,
        ];

        $isCpRequest = request()->isCpRequest();

        $cp = (
            Edition::get()->value < Edition::Pro->value ||
            ($isCpRequest && $user->can('accessCp')) ||
            (Cms::config()->headlessMode && ! Url::isAbsoluteUrl($fePath))
        );
        $scheme = Url::getSchemeForTokenizedUrl($cp);
        $siteId = $isCpRequest ? $user->affiliatedSiteId : null;

        if (! $cp) {
            return Url::siteUrl($fePath, $params, $scheme, siteId: $siteId);
        }

        // Only use cpUrl() if this is a control panel request, or the base control panel URL has been explicitly set,
        // so UrlHelper won't use HTTP_HOST
        if (Cms::config()->baseCpUrl || $isCpRequest) {
            $url = Url::cpUrl($cpPath, $params, $scheme);
        } else {
            $path = Url::prependCpTrigger($cpPath);
            $url = Url::siteUrl($path, $params, $scheme, siteId: $siteId);
        }

        if (Url::isRootRelativeUrl($url) && ! app()->runningInConsole()) {
            return url($url);
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
