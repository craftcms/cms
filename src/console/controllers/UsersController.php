<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers;

use Craft;
use craft\auth\methods\AuthMethodInterface;
use craft\auth\methods\RecoveryCodes;
use craft\console\Controller;
use craft\db\Table;
use craft\elements\User;
use craft\errors\InvalidElementException;
use craft\helpers\ArrayHelper;
use craft\helpers\Console;
use craft\helpers\Db;
use craft\helpers\UrlHelper;
use DateTime;
use Throwable;
use yii\base\InvalidArgumentException;
use yii\console\ExitCode;

/**
 * Manages user accounts.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.6.0
 */
class UsersController extends Controller
{
    /**
     * @var string|null The user’s email address.
     * @since 3.7.0
     */
    public ?string $email = null;

    /**
     * @var string|null The user’s username.
     * @since 3.7.0
     */
    public ?string $username = null;

    /**
     * @var string|null The user’s new password.
     */
    public ?string $password = null;

    /**
     * @var bool|null Whether the user should be an admin.
     * @since 3.7.0
     */
    public ?bool $admin = null;

    /**
     * @var bool|null Whether the user account should be activated.
     * @since 4.1.0
     */
    public ?bool $activate = null;

    /**
     * @var bool Whether to send the user an activation email.
     * @since 5.7.0
     */
    public ?bool $sendActivationEmail = false;

    /**
     * @var string[] The group handles to assign the created user to.
     * @since 3.7.0
     */
    public array $groups = [];

    /**
     * @var int[] The group IDs to assign the user to the created user to.
     * @since 3.7.0
     */
    public array $groupIds = [];

    /**
     * @var string|null The email or username of the user to inherit content when deleting a user.
     * @since 3.7.0
     */
    public ?string $inheritor = null;

    /**
     * @var bool Whether to delete the user’s content if no inheritor is specified.
     * @since 3.7.0
     */
    public bool $deleteContent = false;

    /**
     * @var bool Whether the user should be hard-deleted immediately, instead of soft-deleted.
     * @since 3.7.0
     */
    public bool $hard = false;

    /**
     * @inheritdoc
     */
    public function options($actionID): array
    {
        $options = parent::options($actionID);

        switch ($actionID) {
            case 'create':
                $options[] = 'email';
                $options[] = 'username';
                $options[] = 'password';
                $options[] = 'admin';
                $options[] = 'groups';
                $options[] = 'groupIds';
                $options[] = 'sendActivationEmail';
                break;
            case 'delete':
                $options[] = 'inheritor';
                $options[] = 'deleteContent';
                $options[] = 'hard';
                break;
            case 'set-password':
                $options[] = 'password';
                break;
        }

        return $options;
    }

    protected function defineActions(): array
    {
        return parent::defineActions() + [
            // Fix sluggification of the action ID
            'remove-2fa' => [$this, 'remove2fa'],
        ];
    }

    /**
     * Lists admin users.
     *
     * @return int
     */
    public function actionListAdmins(): int
    {
        /** @var User[] $users */
        $users = User::find()
            ->admin()
            ->status(null)
            ->orderBy(['username' => SORT_ASC])
            ->all();
        $total = count($users);
        $generalConfig = Craft::$app->getConfig()->getGeneral();

        $this->stdout("$total admin " . ($total === 1 ? 'user' : 'users') . ' found:' . PHP_EOL, Console::FG_YELLOW);

        foreach ($users as $user) {
            $this->stdout('    - ');
            if ($generalConfig->useEmailAsUsername) {
                $this->stdout($user->email);
            } else {
                $this->stdout("$user->username ($user->email)");
            }
            switch ($user->getStatus()) {
                case User::STATUS_SUSPENDED:
                    $this->stdout(" [suspended]", Console::FG_RED);
                    break;
                case User::STATUS_ARCHIVED:
                    $this->stdout(" [archived]", Console::FG_RED);
                    break;
                case User::STATUS_PENDING:
                    $this->stdout(" [pending]", Console::FG_YELLOW);
                    break;
            }
            $this->stdout(PHP_EOL);
        }

        return ExitCode::OK;
    }

    /**
     * Creates a user.
     *
     * @return int
     * @since 3.7.0
     */
    public function actionCreate(): int
    {
        if (!Craft::$app->getUsers()->canCreateUsers()) {
            $this->stderr("The maximum number of users has already been reached.\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        // Validate the arguments
        $attributesFromArgs = ArrayHelper::withoutValue([
            'email' => $this->email,
            'username' => $this->username,
            'newPassword' => $this->password,
            'admin' => $this->admin,
        ], null);

        $user = new User($attributesFromArgs);

        if (!empty($attributesFromArgs) && !$user->validate(array_keys($attributesFromArgs))) {
            $this->stderr('Invalid arguments:' . PHP_EOL . '    - ' . implode(PHP_EOL . '    - ', $user->getErrorSummary(true)) . PHP_EOL, Console::FG_RED);
            return ExitCode::USAGE;
        }

        if (Craft::$app->getConfig()->getGeneral()->useEmailAsUsername) {
            $user->username = $this->email ?: $this->prompt('Email:', [
                'required' => true,
                'validator' => $this->createAttributeValidator($user, 'email'),
            ]);
        } else {
            $user->email = $this->email ?: $this->prompt('Email:', [
                'required' => true,
                'validator' => $this->createAttributeValidator($user, 'email'),
            ]);
            $user->username = $this->username ?: $this->prompt('Username:', [
                'required' => true,
                'validator' => $this->createAttributeValidator($user, 'username'),
            ]);
        }

        $user->admin = $this->admin ?? ($this->interactive && $this->confirm('Make this user an admin?'));

        if ($this->password) {
            $user->newPassword = $this->password;
        } elseif ($this->interactive) {
            if ($this->confirm('Set a password for this user?')) {
                $user->newPassword = $this->passwordPrompt([
                    'validator' => $this->createAttributeValidator($user, 'newPassword'),
                ]);
            }
        }

        if ($this->activate !== null) {
            $user->active = $this->activate;
        } else {
            $defaultActivate = $user->newPassword !== null;
            $user->active = $this->interactive ? $this->confirm('Activate the account?', $defaultActivate) : $defaultActivate;
        }

        $this->stdout('Saving the user ... ');

        if (!Craft::$app->getElements()->saveElement($user, false)) {
            $this->stderr('failed:' . PHP_EOL . '    - ' . implode(PHP_EOL . '    - ', $user->getErrorSummary(true)) . PHP_EOL, Console::FG_RED);

            return ExitCode::USAGE;
        }

        $this->stdout('done' . PHP_EOL, Console::FG_GREEN);

        $groupIds = array_merge($this->groupIds, array_map(fn($handle) => Craft::$app->getUserGroups()->getGroupByHandle($handle)->id ?? null, $this->groups));

        if ($groupIds) {
            $this->stdout('Assigning user to groups ... ');

            // Most likely an invalid group ID will throw…
            try {
                Craft::$app->getUsers()->assignUserToGroups($user->id, $groupIds);
            } catch (Throwable) {
                $this->stderr('failed: Couldn’t assign user to specified groups.' . PHP_EOL, Console::FG_RED);
                return ExitCode::UNSPECIFIED_ERROR;
            }

            $this->stdout('done' . PHP_EOL, Console::FG_GREEN);
        }

        if (!$user->active) {
            if ($this->confirm('Send an activation email now?', $this->sendActivationEmail)) {
                $this->stdout('Sending activation email ... ');

                if (Craft::$app->getUsers()->sendActivationEmail($user)) {
                    $this->stdout('done' . PHP_EOL, Console::FG_GREEN);
                    return ExitCode::OK;
                }

                $this->stderr('failed: Couldn’t send activation email.' . PHP_EOL, Console::FG_RED);
            }

            $url = Craft::$app->getUsers()->getActivationUrl($user);
            $this->stdout($this->markdownToAnsi("Activation URL for `$user->username`: "));
            $this->stdout($url . PHP_EOL, Console::FG_CYAN, PHP_EOL);
        }

        return ExitCode::OK;
    }

    /**
     * Generates an activation URL for a pending user.
     *
     * @param string $user The ID, username, or email address of the user account.
     * @return int
     */
    public function actionActivationUrl(string $user): int
    {
        try {
            $user = $this->_user($user);
        } catch (InvalidArgumentException $e) {
            $this->stderr($e->getMessage() . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        if (!$user->pending) {
            $this->stderr("User “{$user->username}” has already been activated." . PHP_EOL, Console::FG_RED);
            return ExitCode::USAGE;
        }

        $url = Craft::$app->getUsers()->getActivationUrl($user);

        $this->stdout("Activation URL for “{$user->username}”: ");
        $this->stdout($url . PHP_EOL, Console::FG_CYAN, PHP_EOL);

        return ExitCode::OK;
    }

    /**
     * Generates a password reset URL for a user.
     *
     * @param string $user The ID, username, or email address of the user account.
     * @return int
     */
    public function actionPasswordResetUrl(string $user): int
    {
        try {
            $user = $this->_user($user);
        } catch (InvalidArgumentException $e) {
            $this->stderr($e->getMessage() . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $url = Craft::$app->getUsers()->getPasswordResetUrl($user);

        $this->stdout("Password reset URL for “{$user->username}”: ");
        $this->stdout($url . PHP_EOL, Console::FG_CYAN, PHP_EOL);

        return ExitCode::OK;
    }

    /**
     * Deletes a user.
     *
     * @param string $user The ID, username, or email address of the user account.
     * @return int
     */
    public function actionDelete(string $user): int
    {
        try {
            $user = $this->_user($user);
        } catch (InvalidArgumentException $e) {
            $this->stderr($e->getMessage() . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        if ($this->deleteContent && $this->inheritor) {
            $this->stderr('Only one of --delete-content or --inheritor may be specified.' . PHP_EOL, Console::FG_RED);
            return ExitCode::USAGE;
        }

        if (!$this->inheritor && $this->interactive && $this->confirm('Transfer this user’s content to an existing user?', true)) {
            $this->inheritor = $this->prompt('Enter the email or username of the user to inherit the content:', [
                'required' => true,
            ]);
        }

        if ($this->inheritor) {
            $inheritor = Craft::$app->getUsers()->getUserByUsernameOrEmail($this->inheritor);

            if (!$inheritor) {
                $this->stderr("No user exists with a username/email of “{$this->inheritor}”." . PHP_EOL, Console::FG_RED);
                return ExitCode::UNSPECIFIED_ERROR;
            }

            if ($this->interactive && !$this->confirm("Delete user “{$user->username}” and transfer their content to user “{$inheritor->username}”?")) {
                $this->stdout('Aborting.' . PHP_EOL);
                return ExitCode::OK;
            }

            $user->inheritorOnDelete = $inheritor;
        } elseif ($this->interactive) {
            $this->deleteContent = $this->confirm("Delete user “{$user->username}” and their content?");

            if (!$this->deleteContent) {
                $this->stdout('Aborting.' . PHP_EOL);
                return ExitCode::OK;
            }
        }

        if (!$user->inheritorOnDelete && !$this->deleteContent) {
            $this->stderr('You must specify either --delete-content or --inheritor to proceed.' . PHP_EOL, Console::FG_RED);
            return ExitCode::USAGE;
        }

        $this->stdout('Deleting the user ... ');

        if (!Craft::$app->getElements()->deleteElement($user, $this->hard)) {
            $this->stderr('failed: Couldn’t delete the user.' . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->stdout('done' . PHP_EOL, Console::FG_GREEN);
        return ExitCode::OK;
    }

    /**
     * Changes a user’s password.
     *
     * @param string $user The ID, username, or email address of the user account.
     * @return int
     */
    public function actionSetPassword(string $user): int
    {
        try {
            $user = $this->_user($user);
        } catch (InvalidArgumentException $e) {
            $this->stderr($e->getMessage() . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $user->setScenario(User::SCENARIO_PASSWORD);

        if ($this->password) {
            $user->newPassword = $this->password;
            if (!$user->validate()) {
                $this->stderr('Unable to set new password on user: ' . $user->getFirstError('newPassword') . PHP_EOL, Console::FG_RED);
                return ExitCode::UNSPECIFIED_ERROR;
            }
        } elseif ($this->interactive) {
            $this->passwordPrompt([
                'validator' => $this->createAttributeValidator($user, 'newPassword'),
            ]);
        }

        $this->stdout('Saving the user ... ');
        Craft::$app->getElements()->saveElement($user, false);
        $this->stdout('done' . PHP_EOL, Console::FG_GREEN);

        return ExitCode::OK;
    }

    /**
     * Generates a URL to impersonate a user.
     *
     * @param string $user The ID, username, or email address of the user account.
     * @return int
     * @since 3.7.15
     */
    public function actionImpersonate(string $user): int
    {
        try {
            $user = $this->_user($user);
        } catch (InvalidArgumentException $e) {
            $this->stderr($e->getMessage() . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $token = Craft::$app->getTokens()->createToken([
            'users/impersonate-with-token', [
                'userId' => $user->id,
                'prevUserId' => $user->id,
            ],
        ], 1, new DateTime('+1 hour'));

        if (!$token) {
            $this->stderr('Unable to create the impersonation token.' . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $url = $user->can('accessCp') ? UrlHelper::cpUrl() : UrlHelper::siteUrl();
        $url = UrlHelper::urlWithToken($url, $token);

        $this->stdout("Impersonation URL for “{$user->username}”: ");
        $this->stdout($url . PHP_EOL, Console::FG_CYAN);
        $this->stdout('(Expires in one hour.)' . PHP_EOL, Console::FG_GREY);

        return ExitCode::OK;
    }

    /**
     * Logs all users out of the system.
     *
     * @return int
     * @since 3.7.33
     */
    public function actionLogoutAll(): int
    {
        $this->stdout('Logging all users out ... ');
        Db::truncateTable(Table::SESSIONS);
        $this->stdout("done\n", Console::FG_GREEN);
        return ExitCode::OK;
    }

    /**
     * Unlocks a user's account.
     *
     * @param string $user The ID, username, or email address of the user account.
     * @return int
     * @since 4.4.0
     */
    public function actionUnlock(string $user): int
    {
        try {
            $user = $this->_user($user);
        } catch (InvalidArgumentException $e) {
            $this->stderr($e->getMessage() . PHP_EOL, Console::FG_RED) . PHP_EOL;
            return ExitCode::UNSPECIFIED_ERROR;
        }

        if (!$user->locked) {
            $this->stdout("User “{$user->username}” is not locked." . PHP_EOL);
            return ExitCode::OK;
        }

        $this->stdout('Unlocking the user ...' . PHP_EOL);
        try {
            Craft::$app->getUsers()->unlockUser($user);
        } catch (InvalidElementException $e) {
            $this->stderr("Failed to unlock user “{$user->username}”: {$e->getMessage()}" . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->stdout("User “{$user->username}” unlocked." . PHP_EOL, Console::FG_GREEN);
        return ExitCode::OK;
    }

    /**
     * Removes user's two-step verification method(s)
     * @param string $user The ID, username, or email address of the user account.
     * @return int
     * @since 5.5.0
     */
    public function remove2fa(string $user): int
    {
        try {
            $user = $this->_user($user);
        } catch (InvalidArgumentException $e) {
            $this->stderr($e->getMessage() . PHP_EOL, Console::FG_RED) . PHP_EOL;
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $authService = Craft::$app->getAuth();
        $activeMethods = $authService->getActiveMethods($user);

        // if user doesn't have any, say so, and we're done
        if (empty($activeMethods)) {
            $this->stdout("User “{$user->username}” doesn’t have any active two-step verification methods." . PHP_EOL);
            return ExitCode::OK;
        }

        $activeMethods = array_combine(
            array_map(fn(AuthMethodInterface $method) => $method::displayName(), $activeMethods),
            $activeMethods,
        );

        // allow removal of all options in one go
        $activeMethods = array_merge(['all' => 'all'], $activeMethods);

        $methodToRemove = $this->select(
            "Which two-step verification method would you like to remove for user “{$user->username}”",
            $activeMethods,
        );

        if ($methodToRemove === 'all') {
            $this->stdout('Removing all two-step verification methods for the user ...' . PHP_EOL);
            unset($activeMethods['all']);
            // remove recovery codes as we'll remove those after the last 2sv method is removed
            unset($activeMethods[RecoveryCodes::displayName()]);

            foreach ($activeMethods as $method) {
                $this->_remove2faMethod($method, $user);
            }
        } else {
            $this->_remove2faMethod($activeMethods[$methodToRemove], $user);
        }

        return ExitCode::OK;
    }

    /**
     * Resolves a `user` argument.
     *
     * @param string $value The `user` argument value
     * @return User
     * @throws InvalidArgumentException if the user could not be found
     */
    private function _user(string $value): User
    {
        if (is_numeric($value)) {
            $user = Craft::$app->getUsers()->getUserById((int)$value);
            if (!$user) {
                throw new InvalidArgumentException("No user exists with the ID: $value");
            }
        } else {
            $user = Craft::$app->getUsers()->getUserByUsernameOrEmail($value);
            if (!$user) {
                throw new InvalidArgumentException("No user exists with the username/email: $value");
            }
        }

        return $user;
    }

    /**
     * Removes auth method for a specific user.
     *
     * @param AuthMethodInterface $method
     * @param User $user
     * @return void
     */
    private function _remove2faMethod(AuthMethodInterface $method, User $user): void
    {
        $this->stdout("   > Removing “{$method::displayName()}” two-step verification method for the user ...");

        $auth = Craft::$app->getAuth();
        $method->remove();

        $this->stdout(" done" . PHP_EOL, Console::FG_GREEN);

        // if that was the last non-Recovery Codes method, remove Recovery Codes too
        if (empty($auth->getActiveMethods($user))) {
            $recoveryCodes = $auth->getMethod(RecoveryCodes::class, $user);
            if ($recoveryCodes->isActive()) {
                $this->stdout("No further two-step verification methods left. Removing “{$recoveryCodes::displayName()}” for the user ...");
                $recoveryCodes->remove();
                $this->stdout(" done" . PHP_EOL, Console::FG_GREEN);
            }
        }
    }
}
