<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Users;

use CraftCms\Cms\Auth\AuthMethods;
use CraftCms\Cms\Auth\Concerns\ConfirmsPasswords;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\Exceptions\InvalidElementException;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Http\ViewModels\UserPasswordViewModel;
use CraftCms\Cms\User\EditUserScreens;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Users;
use CraftCms\Cms\User\Validation\UserRules;
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

    public function index(Request $request, AuthMethods $auth): CpScreenResponse
    {
        $currentUser = $request->craftUser();
        if (! $currentUser) {
            abort(401);
        }

        $user = $currentUser->asElement();

        return $this->asEditUserScreen($user, EditUserScreens::PASSWORD)
            ->inertiaPage('users/Password', new UserPasswordViewModel($user, $auth));
    }

    public function store(Request $request, Elements $elements): Response
    {
        $this->requireConfirmedPassword('An elevated session is required to change your password.');

        $currentUser = $request->craftUser();
        if (! $currentUser) {
            abort(401);
        }

        $user = $currentUser->asElement();

        abort_if(! $user->getHasPassword(), 400, 'Only users with current passwords can set new ones.');

        $validated = $request->validate([
            'newPassword' => ['nullable', 'string', Password::default()],
        ]);

        if (! $request->input('newPassword')) {
            return back();
        }

        $user->newPassword = $validated['newPassword'];
        $user->ruleset->useScenario(UserRules::SCENARIO_PASSWORD);

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

        if ($user->admin) {
            $this->requireAdmin();
        }

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

    public function requireReset(Request $request, Elements $elements, Users $users): Response
    {
        return $this->togglePasswordResetRequirement($request, $elements, $users, required: true);
    }

    public function removeResetRequirement(Request $request, Elements $elements, Users $users): Response
    {
        return $this->togglePasswordResetRequirement($request, $elements, $users, required: false);
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
        $generalConfig = Cms::config();

        // If someone's logged in and they're allowed to edit other users, then see if a userId was submitted
        if (Gate::check('editUsers')) {
            $userId = $request->integer('userId');

            if ($userId) {
                abort_if(! $user = $users->getUserById($userId), 400, 'User not found');
            }
        }

        if (! isset($user)) {
            $validated = $request->validate([
                'loginName' => [
                    'required',
                    'string',
                    Rule::when($generalConfig->useEmailAsUsername, 'email'),
                ],
            ]);

            $loginName = $validated['loginName'];
            $user = $users->getUserByUsernameOrEmail($loginName);

            if (
                ! $user?->getIsCredentialed() ||
                (! $user->getHasPassword() && $user->getHasSsoIdentity())
            ) {
                $errors[] = $generalConfig->useEmailAsUsername
                    ? t('Invalid email.')
                    : t('Invalid username or email.');
            }
        }

        $loginName ??= null;

        return new Timebox()->call(function (Timebox $timebox) use ($loginName, &$errors, $user, $users, $generalConfig): Response {
            // Don't try to send the email if there are already errors or there is no user
            try {
                if (empty($errors) && $user !== null && ! $users->sendPasswordResetEmail($user)) {
                    throw new Exception;
                }
            } catch (Exception) {
                $errors[] = t('There was a problem sending the password reset email.');
            }

            if ($generalConfig->preventUserEnumeration) {
                if (! empty($errors)) {
                    $list = implode("\n", array_map(fn (string $error) => sprintf('- %s', $error), $errors));
                    Log::warning(sprintf("Password reset email not sent:\n%s", $list), [__METHOD__]);
                    $errors = [];
                }
            } else {
                $timebox->returnEarly();
            }

            if (empty($errors)) {
                return $this->asSuccess(t('Check your email for instructions to reset your password.'));
            }

            // Handle the errors.
            return $this->handleSendPasswordResetError($errors, $loginName);
        }, 100_000);
    }

    /** @param list<string> $errors */
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
