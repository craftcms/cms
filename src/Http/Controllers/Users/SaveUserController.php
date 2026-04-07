<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Users;

use Craft;
use craft\web\UploadedFile;
use CraftCms\Cms\Asset\AssetsHelper;
use CraftCms\Cms\Auth\Auth;
use CraftCms\Cms\Auth\Concerns\ConfirmsPasswords;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Image\ImageHelper;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Support\File;
use CraftCms\Cms\Support\Flash;
use CraftCms\Cms\Support\Query;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Users;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

use function CraftCms\Cms\t;

/**
 * @TODO: This is a controller which can be called both with and withoup the cpTrigger. Make sure it is not in the CP folder after we refactor the CP only controllers to be in a CP folder.
 */
readonly class SaveUserController
{
    use ConfirmsPasswords;
    use EditUserTrait;
    use PopulatesNames;
    use RespondsWithFlash;

    public function __construct(
        private Elements $elements,
        private GeneralConfig $generalConfig,
        private ProjectConfig $projectConfig,
        private Sites $sites,
        private Users $users,
    ) {}

    /**
     * Provides an endpoint for saving a user account.
     *
     * This action accounts for the following scenarios:
     * - An admin registering a new user account.
     * - An admin editing an existing user account.
     * - A normal user with user-administration permissions registering a new user account.
     * - A normal user with user-administration permissions editing an existing user account.
     * - A guest registering a new user account ("public registration").
     * This action behaves the same regardless of whether it was requested from the control panel or the front-end site.
     */
    public function __invoke(Request $request): Response
    {
        $canAdministrateUsers = Gate::check('administrateUsers');

        $userSettings = $this->projectConfig->get('users') ?? [];
        $requireEmailVerification = (
            Edition::isAtLeast(Edition::Pro) &&
            ($userSettings['requireEmailVerification'] ?? true)
        );
        $deactivateByDefault = $userSettings['deactivateByDefault'] ?? false;
        $userVariable = $request->getSigned('userVariable') ?? 'user';
        $returnCsrfToken = false;

        // Get the user being edited
        // ---------------------------------------------------------------------

        $userId = $request->input('userId');
        $isNewUser = ! $userId;
        $newEmail = trim($request->input('email') ?? '') ?: null;

        $isPublicRegistration = false;

        // Are we editing an existing user?
        if ($userId) {
            /** @var User $user */
            $user = User::find()
                ->id($userId)
                ->status(null)
                ->addSelect(['users.password', 'users.passwordResetRequired'])
                ->firstOrFail();

            if (! $user->getIsCurrent()) {
                // Make sure they have permission to edit other users
                $this->requirePermission('editUsers');
            }
        } else {
            // Make sure this is Craft Pro, since that's required for having multiple user accounts
            Edition::require(Edition::Team);

            // Is someone logged in?
            if ($request->user()) {
                // Make sure they have permission to register users
                $this->requirePermission('registerUsers');
            } else {
                // Make sure public registration is allowed
                $allowPublicRegistration = $userSettings['allowPublicRegistration'] ?? false;

                abort_if(! $allowPublicRegistration, 403, 'Public registration is not allowed');

                $isPublicRegistration = true;

                // See if there's an inactive user with the same email
                if ($newEmail) {
                    $user = User::find()
                        ->email(Query::escapeParam($newEmail))
                        ->status(User::STATUS_INACTIVE)
                        ->one();
                }
            }

            $user ??= new User;
        }

        $isCurrentUser = $user->getIsCurrent();

        if ($isCurrentUser) {
            // Remember the old username in case it changes
            $oldUsername = $user->username;
        }

        // Handle secure properties (email and password)
        // ---------------------------------------------------------------------

        $sendActivationEmail = false;

        // Are they allowed to set the email address?
        if ($isNewUser || $isCurrentUser || $canAdministrateUsers) {
            // Make sure it actually changed
            if (! $isNewUser && $newEmail && $newEmail === $user->email) {
                $newEmail = null;
            }

            if ($newEmail) {
                // Should we be sending a verification email now?
                // Even if verification isn't required, send one out on account creation if we don't have a password yet
                $sendActivationEmail = (! $isPublicRegistration || ! $deactivateByDefault) && (
                    (
                        $requireEmailVerification && (
                            $isPublicRegistration ||
                            ($isCurrentUser && ! $canAdministrateUsers) ||
                            ($request->input('sendActivationEmail') ?? $request->input('sendVerificationEmail'))
                        )
                    ) ||
                    (
                        ! $requireEmailVerification && $isNewUser && (
                            ($isPublicRegistration && $this->generalConfig->deferPublicRegistrationPassword) ||
                            ($request->input('sendActivationEmail') ?? $request->input('sendVerificationEmail'))
                        )
                    )
                );

                if ($sendActivationEmail) {
                    $user->unverifiedEmail = $newEmail;

                    // Mark them as pending
                    if (! $user->active) {
                        $user->pending = true;
                    }
                } else {
                    // Clear out the unverified email if there is one,
                    // so it doesn't overwrite the new email later on
                    $user->unverifiedEmail = null;
                }

                if (! $sendActivationEmail || $isNewUser) {
                    $user->email = $newEmail;
                }
            }
        } else {
            // Discard the new email if it was posted
            $newEmail = null;
        }

        // Are they allowed to set a new password?
        if ($isPublicRegistration) {
            if (! $this->generalConfig->deferPublicRegistrationPassword) {
                $user->newPassword = $request->input('password', '');
            }
        } elseif ($isCurrentUser) {
            // If there was a newPassword input but it was empty, pretend it didn't exist
            $user->newPassword = $request->input('newPassword') ?: null;
            $returnCsrfToken = $user->newPassword !== null;
        }

        // If editing an existing user and either of these properties are being changed,
        // require the user’s current password for additional security
        if (
            ! $isNewUser &&
            (! empty($newEmail) || $user->newPassword !== null) &&
            ! ($this->isPasswordConfirmed() || $this->existingPasswordVerified($request))
        ) {
            Log::warning('Tried to change the email or password for userId: '.$user->id.', but the current password does not match what the user supplied.', [__METHOD__]);
            $user->errors()->add('currentPassword', t('Incorrect current password.'));
        }

        // Handle the rest of the user properties
        // ---------------------------------------------------------------------

        // Is the site set to use email addresses as usernames?
        if ($this->generalConfig->useEmailAsUsername) {
            $user->username = $user->email;
        } elseif ($isNewUser || $request->user()->admin || $isCurrentUser) {
            $user->username = $request->input('username', ($user->username ?: $user->email));
        }

        $this->populateNameAttributes($request, $user);

        // New users should always be initially saved in a pending state,
        // even if an admin is doing this and opted to not send the verification email
        if ($isNewUser && ! $deactivateByDefault) {
            $user->pending = true;
        }

        if ($canAdministrateUsers) {
            $user->passwordResetRequired = $request->boolean('passwordResetRequired', $user->passwordResetRequired);
        }

        if ($isPublicRegistration) {
            // set the default group on the user, so that any content
            // based on user group condition can be validated and saved against them
            $groups = $this->users->getDefaultUserGroups($user);

            if (! empty($groups)) {
                $user->setGroups($groups);
            }

            // keep track of which site they registered from
            // (do this even if it's not a multi-site install, in case it becomes one later.)
            $user->affiliatedSiteId = $this->sites->getCurrentSite()->id;
        }

        // If this is Craft Pro, grab any profile content from post
        $fieldsLocation = $request->input('fieldsLocation', 'fields');
        $user->setFieldValuesFromRequest($fieldsLocation);

        // Validate and save!
        // ---------------------------------------------------------------------

        $photo = UploadedFile::getInstanceByName('photo');

        if ($photo && ! ImageHelper::canManipulateAsImage($photo->getExtension())) {
            $user->errors()->add('photo', t('The user photo provided is not an image.'));
        }

        // Don't validate required custom fields if it's public registration
        if (! $isPublicRegistration || ($userSettings['validateOnPublicRegistration'] ?? false)) {
            $user->setScenario(Element::SCENARIO_LIVE);
        } else {
            $user->setScenario(User::SCENARIO_REGISTRATION);
        }

        // Manually validate the user so we can pass $clearErrors=false
        $success = $user->validate(null, false) && $this->elements->saveElement($user, false);

        if (! $success) {
            Log::info('User not saved due to validation error.', [__METHOD__]);

            if ($isPublicRegistration) {
                // Move any 'newPassword' errors over to 'password'
                $user->errors()->merge(['password' => $user->errors()->get('newPassword')]);
                $user->errors()->forget('newPassword');
            }

            // Copy any 'unverifiedEmail' errors to 'email'
            if (! $user->errors()->has('email')) {
                $user->errors()->merge(['email' => $user->errors()->get('unverifiedEmail')]);
                $user->errors()->forget('unverifiedEmail');
            }

            return $this->asModelFailure(
                $user,
                mb_ucfirst(t('Couldn’t save {type}.', [
                    'type' => User::lowerDisplayName(),
                ])),
                $userVariable,
            );
        }

        // If this is a new user and email verification isn't required,
        // go ahead and activate them now.
        if ($isNewUser && ! $requireEmailVerification && ! $deactivateByDefault) {
            $this->users->activateUser($user);
        }

        // Is this the current user, and did their username just change?
        if ($isCurrentUser && $user->username !== $oldUsername) {
            // Update the username cookie
            app(Auth::class)->setRememberedUsername($user);
        }

        // Save the user’s photo, if it was submitted
        $this->processUserPhoto($request, app(Elements::class), $user);

        // If this is public registration, assign the user to the default user group
        if (Edition::isAtLeast(Edition::Pro) && $isPublicRegistration) {
            // Assign them to the default user group
            $this->users->assignUserToDefaultGroup($user);
        }

        // Do we need to send a verification email out?
        if ($sendActivationEmail) {
            // Temporarily set the unverified email on the User so the verification email goes to the
            // right place
            $originalEmail = $user->email;
            $user->email = $user->unverifiedEmail;

            $user->sendEmailVerificationNotification();

            // Put the original email back into place
            $user->email = $originalEmail;
        }

        // Is this public registration, and was the user going to be activated automatically?
        $publicActivation = $isPublicRegistration && $user->getStatus() === User::STATUS_ACTIVE;
        $loggedIn = $publicActivation && $this->maybeLoginUserAfterAccountActivation($user);
        $returnCsrfToken = $returnCsrfToken || $loggedIn;

        if ($request->wantsJson()) {
            return $this->asModelSuccess(
                $user,
                t('{type} saved.', ['type' => User::displayName()]),
                $userVariable,
                array_filter([
                    'id' => $user->id, // todo: remove
                    'csrfTokenValue' => $returnCsrfToken && $this->generalConfig->enableCsrfProtection
                        ? csrf_token()
                        : null,
                ]),
            );
        }

        if ($isPublicRegistration) {
            Flash::success(t('User registered.'));
        } else {
            Flash::success(t('{type} saved.', [
                'type' => User::displayName(),
            ]));
        }

        // Is this public registration, and is the user going to be activated automatically?
        if ($publicActivation) {
            return $this->redirectUserToCp($user) ?? $this->redirectUserAfterAccountActivation($user);
        }

        // Tell all browser windows about the draft deletion
        Craft::$app->getSession()->broadcastToJs([
            'event' => 'saveElement',
            'id' => $user->id,
        ]);

        return $this->redirectToPostedUrl($user);
    }

    private function processUserPhoto(Request $request, Elements $elements, User $user): void
    {
        // Delete their photo?
        if ($request->input('deletePhoto')) {
            $this->users->deleteUserPhoto($user);
            $user->photoId = null;
            $elements->saveElement($user);
        }

        $newPhoto = false;
        $fileLocation = null;
        $filename = null;
        $mimeType = null;

        // Did they upload a new one?
        if ($photo = $request->file('photo')) {
            $fileLocation = AssetsHelper::tempFilePath($photo->extension());
            $photo->move(Str::beforeLast($fileLocation, '/'), Str::afterLast($fileLocation, '/'));
            $filename = $photo->getClientOriginalName();
            $mimeType = $photo->getMimeType();
            $newPhoto = true;
        } elseif (($photo = $request->input('photo')) && is_array($photo)) {
            // base64-encoded photo
            $matches = [];

            if (preg_match('/^data:((?<type>[a-z0-9]+\/[a-z0-9+]+);)?base64,(?<data>.+)/i', $photo['data'] ?? '', $matches)) {
                $filename = $photo['filename'] ?? null;
                $extension = $filename ? pathinfo((string) $filename, PATHINFO_EXTENSION) : null;
                $mimeType = $matches['type'] ?: null;

                if (! $extension && $mimeType) {
                    try {
                        $extension = File::getExtensionByMimeType($mimeType);
                    } catch (InvalidArgumentException) {
                    }
                }

                if (! $extension) {
                    Log::warning('Could not determine file extension for user photo.', [__METHOD__]);

                    return;
                }

                $fileLocation = AssetsHelper::tempFilePath($extension);
                $data = base64_decode($matches['data']);
                File::writeToFile($fileLocation, $data);
                $newPhoto = true;
            }
        }

        if ($newPhoto) {
            try {
                $this->users->saveUserPhoto($fileLocation, $user, $filename, $mimeType);
            } catch (Throwable $e) {
                File::delete($fileLocation);

                throw $e;
            }
        }
    }

    /**
     * Possibly log a user in right after they activated their account (not when they reset their password),
     * if Craft is configured to do so.
     *
     * @param  User  $user  The user that was just activated
     * @return bool Whether the user was logged in
     */
    private function maybeLoginUserAfterAccountActivation(User $user): bool
    {
        if (! $this->generalConfig->autoLoginAfterAccountActivation) {
            return false;
        }

        auth('craft')->login($user);

        return true;
    }

    /**
     * Redirects a user to the `postCpLoginRedirect` location, if they have access to the control panel.
     */
    private function redirectUserToCp(User $user): ?Response
    {
        // Can they access the control panel?
        if (! $user->can('accessCp')) {
            return null;
        }

        $postCpLoginRedirect = $this->generalConfig->getPostCpLoginRedirect();
        $url = Url::cpUrl($postCpLoginRedirect);

        return redirect($url);
    }

    /**
     * Redirect the browser after a user’s account has been activated.
     */
    private function redirectUserAfterAccountActivation(User $user): Response
    {
        $activateAccountSuccessPath = $this->generalConfig->getActivateAccountSuccessPath();
        $url = Url::siteUrl($activateAccountSuccessPath);

        return $this->redirectToPostedUrl($user, $url);
    }
}
