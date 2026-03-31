<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Users;

use Craft;
use craft\web\assets\authmethodsetup\AuthMethodSetupAsset;
use CraftCms\Cms\Auth\Concerns\ConfirmsPasswords;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\Exceptions\InvalidElementException;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Users;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Timebox;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

readonly class PasswordController
{
    use ConfirmsPasswords;
    use EditUserTrait;
    use RespondsWithFlash;

    public function index(Request $request): CpScreenResponse
    {
        $user = $request->user();

        $response = $this->asEditUserScreen($user, self::SCREEN_PASSWORD);

        Craft::$app->getView()->registerAssetBundle(AuthMethodSetupAsset::class);

        $response->action('users/save-password');
        $response->contentTemplate('users/_password', compact('user'));

        return $response;
    }

    public function store(Request $request, Elements $elements): Response
    {
        $this->requireConfirmedPassword('An elevated session is required to change your password.');

        /** @var User $user */
        $user = $request->user();

        abort_if(! $user->getHasPassword(), 400, 'Only users with current passwords can set new ones.');

        $validated = $request->validate([
            'newPassword' => ['nullable', 'string', Password::default()],
        ]);

        if (! $request->input('newPassword')) {
            return back();
        }

        $user->newPassword = $validated['newPassword'];
        $user->setScenario(User::SCENARIO_PASSWORD);

        if (! $elements->saveElement($user)) {
            return $this->asFailure(
                t('Couldn’t save password.'),
                $user->errors()->get('newPassword'),
            );
        }

        return $this->asSuccess(t('Password saved.'));
    }

    public function passwordResetUrl(Request $request, Users $users): Response
    {
        $this->requirePermission('administrateUsers');

        abort_unless($this->isPasswordConfirmed() || $this->existingPasswordVerified($request), 400, 'Existing password verification failed');

        $validated = $request->validate([
            'userId' => ['required', 'int', Rule::exists(Table::USERS, 'id')],
        ]);

        $user = $users->getUserById($validated['userId']);

        abort_if(is_null($user), 400, 'User not found');

        try {
            $url = $users->getPasswordResetUrl($user);
        } catch (InvalidElementException $e) {
            if (in_array($user->getStatus(), [User::STATUS_INACTIVE, User::STATUS_PENDING])) {
                return $this->asFailure(t('Couldn’t generate an activation URL: {error}', [
                    'error' => $e->getMessage(),
                ]));
            }

            return $this->asFailure(t('Couldn’t generate a password reset URL: {error}', [
                'error' => $e->getMessage(),
            ]));
        }

        return new JsonResponse([
            'url' => $url,
        ]);
    }

    public function requireReset(Request $request, Users $users): Response
    {
        return $this->togglePasswordResetRequirement($request, $users, required: true);
    }

    public function removeResetRequirement(Request $request, Users $users): Response
    {
        return $this->togglePasswordResetRequirement($request, $users, required: false);
    }

    public function verifyPassword(Request $request): Response
    {
        if ($this->existingPasswordVerified($request)) {
            return $this->asSuccess();
        }

        return $this->asFailure(t('Invalid password.'));
    }

    private function togglePasswordResetRequirement(Request $request, Elements $elements, Users $users, bool $required): Response
    {
        $this->requirePermission('administrateUsers');

        $request->validate([
            'userId' => ['required', 'int', Rule::exists(Table::USERS, 'id')],
        ]);

        $user = $users->getUserById($request->integer('userId'));

        abort_if(is_null($user), 400, 'User not found');

        $user->passwordResetRequired = $required;

        if (! $elements->saveElement($user, false)) {
            return $this->asFailure(t('Couldn’t save {type}.', [
                'type' => User::lowerDisplayName(),
            ]));
        }

        return $this->asSuccess(t('{type} saved.', [
            'type' => User::displayName(),
        ]));
    }

    public function sendPasswordResetEmail(Request $request, Users $users): Response
    {
        $errors = [];
        $loginName = null;

        // If someone's logged in and they're allowed to edit other users, then see if a userId was submitted
        if (Gate::check('editUsers')) {
            $userId = $request->integer('userId');

            if ($userId) {
                abort_if(! $user = $users->getUserById($userId), 400, 'User not found');
            }
        }

        if (! isset($user)) {
            $loginName = $request->input('loginName');

            if (! $loginName) {
                // If they didn't even enter a username/email, just bail now.
                $errors[] = Cms::config()->useEmailAsUsername
                    ? t('Email is required.')
                    : t('Username or email is required.');

                return $this->handleSendPasswordResetError($errors);
            }

            $user = $users->getUserByUsernameOrEmail($loginName);

            if (
                ! $user?->getIsCredentialed() ||
                (! $user->getHasPassword() && $user->getHasSsoIdentity())
            ) {
                $errors[] = Cms::config()->useEmailAsUsername
                    ? t('Invalid email.')
                    : t('Invalid username or email.');
            }
        }

        return new Timebox()->call(function (Timebox $timebox) use ($loginName, &$errors, $user, $users): Response {
            // Don't try to send the email if there are already errors or there is no user
            try {
                if (empty($errors) && $user !== null && ! $users->sendPasswordResetEmail($user)) {
                    throw new Exception;
                }
            } catch (Exception) {
                $errors[] = t('There was a problem sending the password reset email.');
            }

            if (Cms::config()->preventUserEnumeration) {
                if (! empty($errors)) {
                    $list = implode("\n", array_map(fn (string $error) => sprintf('- %s', $error), $errors));
                    Log::warning(sprintf("Password reset email not sent:\n%s", $list), [__METHOD__]);
                    $errors = [];
                }
            } else {
                $timebox->returnEarly();
            }

            if (empty($errors)) {
                return $this->asSuccess(t('Password reset email sent.'));
            }

            // Handle the errors.
            return $this->handleSendPasswordResetError($errors, $loginName);
        }, 100_000);
    }

    private function handleSendPasswordResetError(array $errors, ?string $loginName = null): Response
    {
        $errorString = implode(', ', $errors);

        return $this->asFailure(
            $errorString,
            [
                'errors' => $errors,
                'loginName' => $loginName,
            ],
        );
    }
}
