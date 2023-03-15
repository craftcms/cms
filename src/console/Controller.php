<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console;

use craft\console\controllers\ResaveController;
use craft\events\DefineConsoleActionsEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\Console;
use craft\helpers\FileHelper;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use Seld\CliPrompt\CliPrompt;
use Throwable;
use yii\base\Action;
use yii\base\InvalidConfigException;
use yii\console\Controller as YiiController;
use yii\helpers\Inflector;

/**
 * Base console controller
 *
 * @property Request $request
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2.0
 */
class Controller extends YiiController
{
    use ControllerTrait {
        ControllerTrait::init as private traitInit;
        ControllerTrait::options as private traitOptions;
        ControllerTrait::runAction as private traitRunAction;
    }

    /**
     * @event DefineConsoleActionsEvent The event that is triggered when defining custom actions for this controller.
     *
     * See [[defineActions()]] for details on what to set on `$event->actions`.
     * ---
     * ```php
     * use craft\events\DefineConsoleActionsEvent;
     * use craft\console\Controller;
     * use craft\console\controllers\ResaveController;
     * use yii\base\Event;
     *
     * Event::on(ResaveController::class,
     *     Controller::EVENT_DEFINE_ACTIONS,
     *     function(DefineConsoleActionsEvent $event) {
     *         $event->actions['products'] = [
     *             'options' => ['type'],
     *             'helpSummary' => 'Re-saves products.',
     *             'action' => function($params): int {
     *                 // @var ResaveController $controller
     *                 $controller = Craft::$app->controller;
     *                 $criteria = [];
     *                 if ($controller->type) {
     *                     $criteria['type'] = explode(',', $controller->type);
     *                 }
     *                 return $controller->resaveElements(Product::class, $criteria);
     *             }
     *         ];
     *     }
     * );
     * ```
     */
    public const EVENT_DEFINE_ACTIONS = 'defineActions';

    /**
     * @var array Custom actions that should be available.
     * @see defineActions()
     */
    private array $_actions;

    /**
     * @var ReflectionFunctionAbstract[] Memoized reflection objects
     * @see getActionMethodReflection()
     */
    private array $_reflections = [];

    /**
     * @var string|null The active action ID.
     * @see runAction()
     */
    private ?string $_actionId = null;

    /**
     * @inheritdoc
     */
    public function __isset($name)
    {
        if ($this->_isCustomOption($name)) {
            return $this->_actions[$this->_actionId]['options'][$name] !== null;
        }

        return parent::__isset($name);
    }

    /**
     * @inheritdoc
     */
    public function __get($name)
    {
        if ($this->_isCustomOption($name)) {
            return $this->_actions[$this->_actionId]['options'][$name];
        }

        return parent::__get($name);
    }

    /**
     * @inheritdoc
     */
    public function __set($name, $value)
    {
        if ($this->_isCustomOption($name)) {
            $this->_actions[$this->_actionId]['options'][$name] = $value;
            return;
        }

        parent::__set($name, $value);
    }

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    public function init(): void
    {
        $this->traitInit();

        $this->_actions = [];
        foreach ($this->defineActions() as $id => $action) {
            if (is_callable($action)) {
                $action = ['action' => $action];
            }

            if (!isset($action['action'])) {
                throw new InvalidConfigException("Action '$id' is missing an 'action' key.");
            }

            if (is_callable($action['action'])) {
                $action['action'] = [
                    'class' => CallableAction::class,
                    'callable' => $action['action'],
                ];
            }

            // Normalize the options
            if (isset($action['options'])) {
                $options = [];
                foreach ($action['options'] as $k => $v) {
                    if (is_int($k)) {
                        $options[$v] = null;
                    } else {
                        $options[$k] = $v;
                    }
                }
                $action['options'] = $options;
            }

            $this->_actions[$id] = $action;
        }
    }

    /**
     * @inheritdoc
     */
    public function actions(): array
    {
        return ArrayHelper::getColumn($this->_actions, 'action');
    }

    /**
     * @inheritdoc
     */
    public function options($actionID): array
    {
        $options = $this->traitOptions($actionID);

        if (isset($this->_actions[$actionID]['options'])) {
            $options = array_merge($options, array_keys($this->_actions[$actionID]['options']));
        }

        return $options;
    }

    /**
     * @inheritdoc
     */
    public function run($route, $params = [])
    {
        // Pass along the common params
        $passedOptions = $this->getPassedOptionValues();
        foreach (['interactive', 'color', 'silentExitOnException'] as $param) {
            if (!array_key_exists($param, $params) && array_key_exists($param, $passedOptions)) {
                $params[$param] = $passedOptions[$param];
            }
        }

        return parent::run($route, $params);
    }

    /**
     * @inheritdoc
     */
    public function runAction($id, $params = []): int
    {
        $this->_actionId = $id;
        $result = $this->traitRunAction($id, $params);
        $this->_actionId = null;
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getActionHelpSummary($action): string
    {
        if (isset($this->_actions[$action->id])) {
            $help = $this->_actions[$action->id]['helpSummary'] ?? $this->_actions[$action->id]['help'] ?? '';
            return preg_split('/\R/u', $help)[0];
        }

        return parent::getActionHelpSummary($action);
    }

    /**
     * @inheritdoc
     */
    public function getActionHelp($action): string
    {
        if (isset($this->_actions[$action->id])) {
            return $this->_actions[$action->id]['help'] ?? $this->_actions[$action->id]['helpSummary'] ?? '';
        }

        return parent::getActionHelp($action);
    }

    /**
     * @inheritdoc
     */
    public function getActionArgsHelp($action): array
    {
        $args = parent::getActionArgsHelp($action);

        if (isset($this->_actions[$action->id])) {
            foreach ($args as $name => &$arg) {
                if (isset($this->_actions[$action->id]['argsHelp'][$name])) {
                    $arg['comment'] = $this->_actions[$action->id]['argsHelp'][$name];
                }
            }
        }

        return $args;
    }

    /**
     * @inheritdoc
     */
    public function getActionOptionsHelp($action): array
    {
        $options = parent::getActionOptionsHelp($action);

        if (isset($this->_actions[$action->id]['options'])) {
            foreach ($this->_actions[$action->id]['options'] as $name => $value) {
                $options[Inflector::camel2id($name, '-', true)] = [
                    'type' => $value === null ? 'string|null' : gettype($value),
                    'default' => $value,
                    'comment' => $this->_actions[$action->id]['optionsHelp'][$name] ?? null,
                ];
            }
        }

        return $options;
    }

    /**
     * Returns an array of custom actions that should be available on the controller.
     *
     * The keys of this array should be the action IDs, and the values can be callables or
     * sub-arrays with the following keys:
     *
     * - `action` – A callable that is responsible for running the action
     * - `options` – An array of options that should be available to the command. Options can either be defined as
     *   strings (`['option1', 'option2']`) or key/value pairs (`['option1' => 'defaultValue']`).
     * - `helpSummary` – Help summary text for the action (shown when running `craft help controller-id`)
     * - `help` – Help text for the action (shown when running `craft help controller-id/action-id`)
     * - `argsHelp` – Sub-array that defines help text for the arguments, indexed by argument names
     *   (shown when running `craft help controller-id/action-id`)
     * - `optionsHelp` – Sub-array that defines help text for the options, indexed by option names
     *   (shown when running `craft help controller-id/action-id`)
     *
     * @return array
     */
    protected function defineActions(): array
    {
        $event = new DefineConsoleActionsEvent();
        $this->trigger(self::EVENT_DEFINE_ACTIONS, $event);
        return $event->actions;
    }

    /**
     * @param Action $action
     * @return ReflectionFunctionAbstract
     */
    protected function getActionMethodReflection($action): ReflectionFunctionAbstract
    {
        if ($action instanceof CallableAction) {
            if (!isset($this->_reflections[$action->id])) {
                if (is_array($action->callable)) {
                    $this->_reflections[$action->id] = new ReflectionMethod($action->callable[0], $action->callable[1]);
                } else {
                    $this->_reflections[$action->id] = new ReflectionFunction($action->callable);
                }
            }
            return $this->_reflections[$action->id];
        }

        return parent::getActionMethodReflection($action);
    }

    /**
     * Returns whether the given option is defined by a custom action.
     *
     * @param string $name
     * @return bool
     */
    private function _isCustomOption(string $name): bool
    {
        return (
            isset($this->_actionId) &&
            isset($this->_actions[$this->_actionId]['options']) &&
            array_key_exists($name, $this->_actions[$this->_actionId]['options'])
        );
    }

    /**
     * Prompts the user for a password and validates it.
     *
     * @param array $options options to customize the behavior of the prompt:
     *
     * - `label`: the prompt label
     * - `required`: whether it is required or not (true by default)
     * - `validator`: a callable function to validate input. The function must accept two parameters:
     *     - `$input`: the user input to validate
     *     - `$error`: the error value passed by reference if validation failed
     * - `error`: the error message to show if the password is invalid
     * - `confirm`: whether the user should be prompted for the password a second time to confirm their input
     *   (true by default)
     *
     * An example of how to use the prompt method with a validator function.
     *
     * ```php
     * $code = $this->passwordPrompt('Enter 4-Chars-Pin', ['required' => true, 'validator' => function($input, &$error) {
     *     if (strlen($input) !== 4) {
     *         $error = 'The Pin must be exactly 4 chars!';
     *         return false;
     *     }
     *     return true;
     * }]);
     * ```
     *
     * @return string the user input
     * @since 3.6.0
     */
    public function passwordPrompt(array $options = []): string
    {
        $options += [
            'label' => 'Password: ',
            'required' => true,
            'validator' => null,
            'error' => 'Invalid input.',
            'confirm' => true,
        ];

        $options['label'] = StringHelper::ensureRight($options['label'], ' ');

        // todo: would be nice to replace CliPrompt with a native Yii silent prompt
        // (https://github.com/yiisoft/yii2/issues/10551)
        top:
        $this->stdout($options['label']);
        $input = CliPrompt::hiddenPrompt(true);

        if ($options['required'] && $input === '') {
            $this->stdout($options['error'] . PHP_EOL);
            goto top;
        }

        $error = null;

        if ($options['validator'] && !$options['validator']($input, $error)) {
            /** @var string|null $error */
            $this->stdout(($error ?? $options['error']) . PHP_EOL);
            goto top;
        }

        if ($options['confirm']) {
            $this->stdout('Confirm: ');
            if ($input !== CliPrompt::hiddenPrompt(true)) {
                $this->stdout('Passwords didn\'t match, try again.' . PHP_EOL, Console::FG_RED);
                goto top;
            }
        }

        return $input;
    }

    /**
     * Outputs a table via [[Console::table()]].
     *
     * @param string[]|array[] $headers The table headers
     * @param array[] $data The table data
     * @param array $options
     * @since 3.7.23
     */
    public function table(array $headers, array $data, array $options = []): void
    {
        $options += [
            'colors' => $this->isColorEnabled(),
        ];

        Console::table($headers, $data, $options);
    }

    /**
     * Performs an action with descriptive output.
     *
     * @param string $description The action description. Supports Markdown formatting.
     * @param callable $action The action callable
     * @param bool $withDuration Whether to output the action duration upon completion
     * @since 4.3.5
     */
    public function do(string $description, callable $action, bool $withDuration = false): void
    {
        $this->stdout(' → ', Console::FG_GREY);
        $this->stdout($this->markdownToAnsi($description));
        $this->stdout(' … ', Console::FG_GREY);

        if ($withDuration) {
            $time = microtime(true);
        }

        try {
            $action();
        } catch (Throwable $e) {
            $this->stdout('✕' . PHP_EOL, Console::FG_RED, Console::BOLD);
            $this->stdout("   Error: {$e->getMessage()}" . PHP_EOL, Console::FG_RED);
            throw $e;
        }

        $this->stdout('✓', Console::FG_GREEN, Console::BOLD);
        if ($withDuration) {
            $this->stdout(sprintf(' (time: %.3fs', microtime(true) - $time), Console::FG_GREY);
        }
        $this->stdout(PHP_EOL);
    }

    /**
     * Creates a directory, and outputs to the console.
     *
     * @param string $path The path to the directory
     * @since 4.3.5
     */
    public function createDirectory(string $path): void
    {
        $path = FileHelper::relativePath($path);
        $this->do(
            sprintf('Creating %s', $this->ansiFormat("$path/", Console::FG_CYAN)),
            function() use ($path) {
                FileHelper::createDirectory($path);
            },
        );
    }

    /**
     * Writes contents to a file, and outputs to the console.
     *
     * @param string $file The path to the file to write to
     * @param string $contents The file contents
     * @param array $options Options for [[FileHelper::writeToFile()]]
     * @since 4.3.5
     */
    public function writeToFile(string $file, string $contents, array $options = []): void
    {
        $file = FileHelper::relativePath($file);
        $description = file_exists($file) ? "Updating `$file`" : "Creating `$file`";
        $this->do($description, function() use ($file, $contents, $options) {
            FileHelper::writeToFile($file, $contents, $options);
        });
    }

    /**
     * JSON-encodes a value and writes it to a file.
     *
     * @param string $file The path to the file to write to
     * @param mixed $value The value to be JSON-encoded and written out
     * @since 4.3.5
     */
    public function writeJson(string $file, mixed $value): void
    {
        $json = Json::encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
        $this->writeToFile($file, "$json\n");
    }
}
