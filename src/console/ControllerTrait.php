<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console;

use Composer\Util\Platform;
use Composer\Util\Silencer;
use Craft;
use craft\base\Model;
use craft\helpers\App;
use craft\helpers\Console;
use craft\mutex\Mutex as CraftMutex;
use yii\base\Action;
use yii\base\InvalidRouteException;
use yii\console\Exception;
use yii\redis\Mutex as RedisMutex;

/**
 * ConsoleControllerTrait implements the common methods and properties for console controllers.
 *
 * @mixin \yii\console\Controller
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2.0
 */
trait ControllerTrait
{
    /**
     * Whether the command should ensure it is only being run once at a time.
     *
     * If this is passed and the same command is already being run in a separate shell/environment,
     * the command will abort with an exit code of 1.
     *
     * @since 4.4.0
     */
    public bool $isolated = false;

    private ?string $isolationMutexName = null;

    /**
     * Initializes the object.
     *
     * @see \yii\base\BaseObject::init()
     * @since 4.4.0
     */
    public function init()
    {
        parent::init();
        $this->checkTty();
    }

    /**
     * Returns the names of valid options for the action (id).
     *
     * @param string $actionID The action ID of the current request.
     * @return string[] The names of the options valid for the action.
     * @see \yii\console\Controller::options()
     * @since 4.4.0
     */
    public function options($actionID): array
    {
        $options = parent::options($actionID);
        $options[] = 'isolated';
        return $options;
    }

    /**
     * Runs an action within this controller with the specified action ID and parameters.
     *
     * Runs an action with the specified action ID and parameters.
     * If the action ID is empty, the method will use [[defaultAction]].
     * @param string $id The ID of the action to be executed.
     * @param array $params The parameters (name-value pairs) to be passed to the action.
     * @return int The status of the action execution. 0 means normal, other values mean abnormal.
     * @throws InvalidRouteException if the requested action ID cannot be resolved into an action successfully.
     * @throws Exception if there are unknown options or missing arguments
     * @see \yii\console\Controller::runAction()
     * @since 4.4.0
     */
    public function runAction($id, $params = [])
    {
        try {
            return parent::runAction($id, $params);
        } finally {
            if (isset($this->isolationMutexName)) {
                Craft::$app->getMutex()->release($this->isolationMutexName);
            }
        }
    }

    /**
     * This method is invoked right before an action is executed.
     *
     * @param Action $action the action to be executed.
     * @return bool whether the action should continue to run.
     * @see \yii\base\Controller::beforeAction()
     * @since 4.4.0
     */
    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        // Make sure this isn't a root user
        if (!$this->checkRootUser()) {
            return false;
        }

        if ($this->isolated) {
            $uniqueId = $action->getUniqueId();
            $name = "isolated-command:$uniqueId";

            $mutex = Craft::$app->getMutex();
            if (!$mutex->acquire($name)) {
                $this->stderr("The $uniqueId command is already running.\n", Console::FG_RED);
                return false;
            }

            // Remember the lock name for runAction()
            $this->isolationMutexName = $name;

            // If they're using a Redis mutex, make sure it’s set to a 15 minute duration
            if ($mutex instanceof RedisMutex) {
                $expire = $mutex->expire;
            } elseif ($mutex instanceof CraftMutex && $mutex->mutex instanceof RedisMutex) {
                $expire = $mutex->mutex->expire;
            } else {
                $expire = false;
            }

            if ($expire !== false && $expire < 900) {
                $this->warning(<<<MD
The `mutex` component is configured to let locks expire after $expire seconds.
To ensure `--isolated` works reliably, modify the component definition in
`config/app.php` so `expire` is set to 900 seconds for console requests:

```php
'mutex' => function() {
    \$config = [
        'class' => craft\\mutex\\Mutex::class,
        'mutex' => [
            'class' => yii\\redis\\Mutex::class,
            'expire' => Craft::\$app->request->isConsoleRequest ? 900 : 30,
            // ...
        ],
    ];
    return Craft::createObject(\$config);
},
```
MD
                );
            }
        }

        return true;
    }

    /**
     * Sets [[\yii\console\Controller::$interactive]] to `false` if this isn’t a TTY shell.
     *
     * @since 3.6.1
     */
    protected function checkTty(): void
    {
        // Don't treat this as interactive if it doesn't appear to be a TTY shell
        if ($this->interactive && !Platform::isTty()) {
            $this->interactive = false;
        }
    }

    /**
     * Returns whether a command should be executed depending on whether it's being run as a root user,
     * and whether they're OK with that.
     *
     * @return bool
     * @since 3.7.0
     */
    protected function checkRootUser(): bool
    {
        if (Platform::isWindows() || !function_exists('exec') || App::env('CRAFT_ALLOW_SUPERUSER')) {
            return true;
        }

        // Check if we're running as root. Borrowed heavily from
        // https://github.com/composer/composer/blob/master/src/Composer/Console/Application.php
        if (function_exists('posix_getuid') && posix_getuid() === 0) {
            $this->stdout('Craft commands should not be run as the root/super user.' . PHP_EOL, Console::FG_RED);
            $this->stdout('See https://craftcms.com/knowledge-base/craft-console-root for details on why that’s a bad idea.' . PHP_EOL, Console::FG_GREY);

            if ($this->interactive && !$this->confirm('Proceed anyway?')) {
                return false;
            }

            if ($uid = (int)getenv('SUDO_UID')) {
                // Silently clobber any sudo credentials on the invoking user to avoid privilege escalations later on
                // ref. https://github.com/composer/composer/issues/5119
                /** @noinspection CommandExecutionAsSuperUserInspection */
                Silencer::call('exec', "sudo -u \\#$uid sudo -K > /dev/null 2>&1");
            }
        }

        // Silently clobber any remaining sudo leases on the current user as well to avoid privilege escalations
        /** @noinspection CommandExecutionAsSuperUserInspection */
        Silencer::call('exec', 'sudo -K > /dev/null 2>&1');

        return true;
    }

    /**
     * @param string $command
     * @param bool $withScriptName
     */
    protected function outputCommand(string $command, bool $withScriptName = true): void
    {
        Console::outputCommand($command, $withScriptName);
    }

    /**
     * Creates a function for the `validator` option of `Controller::prompt`.
     *
     * @param Model $model
     * @param string $attribute
     * @return callable
     * @since 3.7.0
     */
    protected function createAttributeValidator(Model $model, string $attribute): callable
    {
        return function($input, ?string &$error) use ($model, $attribute) {
            $model->$attribute = $input;

            if (!$model->validate([$attribute])) {
                $error = $model->getFirstError($attribute);

                return false;
            }
            $error = null;

            return true;
        };
    }

    /**
     * Outputs a note to the console.
     *
     * @param string $message The message. Supports Markdown formatting.
     * @since 4.4.0
     */
    public function note(string $message, string $icon = 'ℹ️ '): void
    {
        $this->stdout("\n$icon ", Console::FG_YELLOW, Console::BOLD);
        $this->stdout(trim(preg_replace('/^/m', '   ', $this->markdownToAnsi($message))) . "\n\n");
    }

    /**
     * Outputs a success message to the console.
     *
     * @param string $message The message. Supports Markdown formatting.
     * @since 4.4.0
     */
    public function success(string $message): void
    {
        $this->note($message, '✅');
    }

    /**
     * Outputs a failure message to the console.
     *
     * @param string $message The message. Supports Markdown formatting.
     * @since 4.4.0
     */
    public function failure(string $message): void
    {
        $this->note($message, '❌');
    }

    /**
     * Outputs a tip to the console.
     *
     * @param string $message The message. Supports Markdown formatting.
     * @since 4.4.0
     */
    public function tip(string $message): void
    {
        $this->note($message, '💡');
    }

    /**
     * Outputs a warning to the console.
     *
     * @param string $message The message. Supports Markdown formatting.
     * @since 4.4.0
     */
    public function warning(string $message): void
    {
        $this->note($message, '⚠️ ');
    }

    /**
     * Converts Markdown to be better readable in console environments by applying some ANSI format.
     *
     * @param string $markdown
     * @return string
     * @since 4.4.0
     */
    public function markdownToAnsi(string $markdown): string
    {
        if (!$this->isColorEnabled()) {
            return $markdown;
        }

        return trim(Console::markdownToAnsi($markdown));
    }
}
