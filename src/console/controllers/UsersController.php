<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers;

use Craft;
use craft\console\Controller;
use craft\elements\User;
use craft\helpers\Console;
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
     * @var string|null The user’s new password.
     */
    public $password;

    /**
     * @inheritdoc
     */
    public function options($actionID)
    {
        $options = parent::options($actionID);

        if ($actionID === 'set-password') {
            $options[] = 'password';
        }

        return $options;
    }

    /**
     * Lists admin users.
     *
     * @return int
     */
    public function actionListAdmins(): int
    {
        $users = User::find()
            ->admin()
            ->anyStatus()
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
     * Changes a user’s password.
     *
     * @param string $usernameOrEmail The user’s username or email address
     * @return int
     */
    public function actionSetPassword(string $usernameOrEmail): int
    {
        $user = Craft::$app->getUsers()->getUserByUsernameOrEmail($usernameOrEmail);

        if (!$user) {
            $this->stderr("No user exists with a username/email of “{$usernameOrEmail}”." . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $user->setScenario(User::SCENARIO_PASSWORD);

        if ($this->password) {
            $user->newPassword = $this->password;
            if (!$user->validate()) {
                $this->stderr('Unable to set new password on user: ' . $user->getFirstError('newPassword') . PHP_EOL, Console::FG_RED);
                return ExitCode::UNSPECIFIED_ERROR;
            }
        } else {
            $this->passwordPrompt([
                'validator' => function(string $input, string &$error = null) use ($user) {
                    $user->newPassword = $input;
                    if (!$user->validate()) {
                        $error = $user->getFirstError('newPassword');
                        return false;
                    }
                    return true;
                }
            ]);
        }

        $this->stdout('Saving the user ... ');
        Craft::$app->getElements()->saveElement($user, false);
        $this->stdout('done' . PHP_EOL, Console::FG_GREEN);

        return ExitCode::OK;
    }
}
