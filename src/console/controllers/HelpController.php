<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers;

use Craft;
use craft\helpers\App;
use craft\helpers\Json;
use ReflectionFunctionAbstract;
use Throwable;
use yii\base\InvalidConfigException;
use yii\console\Controller;
use yii\console\controllers\HelpController as BaseHelpController;
use yii\console\Exception;
use yii\console\ExitCode;
use yii\helpers\Console;
use yii\helpers\Inflector;

/**
 * Provides help information about console commands.
 *
 * This command displays the available command list in
 * the application or the detailed instructions about using
 * a specific command.
 *
 * This command can be used as follows on command line:
 *
 * ```
 * yii help [command name]
 * ```
 *
 * In the above, if the command name is not provided, all
 * available commands will be displayed.
 *
 * @since 3.7.56
 */
class HelpController extends BaseHelpController
{
    /**
     * @var bool Should the commands help be returned in JSON format?
     */
    public $asJson = false;

    /**
     * @inheritdoc
     */
    public function options($actionID): array
    {
        $options = parent::options($actionID);

        switch ($actionID) {
            case 'index':
                $options[] = 'asJson';
                break;
        }

        return $options;
    }

    /**
     * @inheritdoc
     */
    public function optionAliases(): array
    {
        $aliases = parent::optionAliases();
        $aliases['j'] = 'asJson';
        return $aliases;
    }

    /**
     * Displays available commands or the detailed information
     * about a particular command.
     *
     * @param string $command The name of the command to show help about.
     * If not provided, all available commands will be displayed.
     * @return int the exit status
     * @throws Exception if the command for help is unknown
     */
    public function actionIndex($command = null): int
    {
        // If they don't want JSON, let the parent do its thing
        if (!$this->asJson) {
            parent::actionIndex($command);
            return ExitCode::OK;
        }

        // Get the command info to output
        if ($command !== null) {
            $data = $this->commandInfo($command);
        } else {
            $data = [
                'commands' => $this->allCommandsInfo(),
            ];
        }

        // Send the commands encoded as JSON to stdout
        $jsonOptions = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | (App::devMode() ? JSON_PRETTY_PRINT : 0);
        $this->stdout(Json::encode($data, $jsonOptions) . PHP_EOL);
        return ExitCode::OK;
    }

    /**
     * Return an array of information on the passed in CLI $command
     *
     * @param string $command
     * @return array
     * @throws Exception
     * @throws InvalidConfigException
     */
    protected function commandInfo(string $command): array
    {
        $commandInfo = [];

        $result = Craft::$app->createController($command);
        if ($result === false) {
            $name = $this->ansiFormat($command, Console::FG_YELLOW);
            throw new Exception("No help for unknown command \"$name\".");
        }

        /** @var Controller $controller */
        /** @var string $actionId */
        [$controller, $actionId] = $result;

        // Try/catch in case an exception is thrown during reflection
        try {
            $action = $controller->createAction($actionId);
            // Get the command description, args, and options
            $description = $this->unformattedActionHelp($controller->getActionMethodReflection($action));
            $args = $controller->getActionArgsHelp($action);
            $options = $controller->getActionOptionsHelp($action);
            $optionNames = array_keys($options);

            // Index the option aliases by option name
            $optionAliases = [];
            foreach ($controller->optionAliases() as $alias => $name) {
                $name = Inflector::camel2id($name);
                $optionAliases[$name][] = "-$alias";
            }

            return [
                'name' => $command,
                'description' => $description,
                'definition' => [
                    'arguments' => array_map(fn($name, $info) => [
                        'name' => $name,
                        'required' => $info['required'],
                        'type' => $info['type'],
                        'description' => $this->commentCleanup($info['comment']),
                        'default' => $info['default'],
                    ], array_keys($args), array_values($args)),
                    'options' => array_combine(
                        $optionNames,
                        array_map(fn($name, $info) => [
                            'name' => '--' . $name,
                            'shortcut' => implode('|', $optionAliases[$name] ?? []),
                            'type' => $info['type'],
                            'description' => $this->commentCleanup($info['comment']),
                            'default' => $info['default'],
                        ], $optionNames, array_values($options))
                    ),
                ],
            ];
        } catch (Throwable $e) {
            $this->stderr($e->getMessage());
        }

        return $commandInfo;
    }

    /**
     * Return an array of information for every CLI command
     *
     * @return array
     * @throws Exception
     * @throws InvalidConfigException
     */
    protected function allCommandsInfo(): array
    {
        $commandNames = [];

        // Get all of the command names
        foreach ($this->getCommandDescriptions() as $command => $description) {
            $result = Craft::$app->createController($command);
            /** @var Controller $controller */
            [$controller] = $result;
            $actions = $this->getActions($controller);
            $prefix = $controller->getUniqueId();

            if ($controller->createAction($controller->defaultAction) !== null) {
                $commandNames[] = $prefix;
            }

            foreach ($actions as $action) {
                $commandNames[] = "$prefix/$action";
            }
        }

        // Get information on each command name
        $commandsInfo = [];
        foreach ($commandNames as $commandName) {
            $commandInfo = $this->commandInfo($commandName);
            if (!empty($commandInfo)) {
                $commandsInfo[] = $commandInfo;
            }
        }

        return $commandsInfo;
    }

    /**
     * Returns full description from the docblock without any kind of ANSI terminal formatting
     *
     * @see Controller::getActionHelp()
     * @param ReflectionFunctionAbstract $reflection
     * @return string
     */
    protected function unformattedActionHelp(ReflectionFunctionAbstract $reflection): string
    {
        $comment = strtr(trim(preg_replace('/^\s*\**( |\t)?/m', '', trim($reflection->getDocComment(), '/'))), "\r", '');
        if (preg_match('/^\s*@\w+/m', $comment, $matches, PREG_OFFSET_CAPTURE)) {
            $comment = trim(substr($comment, 0, $matches[0][1]));
        }

        return $this->commentCleanup($comment);
    }

    /**
     * Cleans up a comment.
     *
     * @param string $comment
     * @return string
     */
    protected function commentCleanup(string $comment): string
    {
        return trim(preg_replace('/\s+/', ' ', $comment));
    }
}
