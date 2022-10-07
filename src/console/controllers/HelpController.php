<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers;

use Craft;
use craft\helpers\Json;
use ReflectionMethod;
use Throwable;
use yii\base\InvalidConfigException;
use yii\console\Controller;
use yii\console\controllers\HelpController as BaseHelpController;
use yii\console\Exception;
use yii\console\ExitCode;
use yii\helpers\Console;

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
        $options[] = 'asJson';

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
            $commands = $this->commandInfo($command);
        } else {
            $commands = $this->allCommandsInfo();
        }

        // Send the commands encoded as JSON to stdout
        $jsonOptions = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | (YII_DEBUG ? JSON_PRETTY_PRINT : 0);
        $this->stdout(Json::encode($commands, $jsonOptions) . PHP_EOL);
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

            return array_filter([
                'name' => $command,
                'description' => $description,
                'args' => array_map(function($k, $v) {
                    return array_filter([
                        'name' => $k,
                        'description' => ($v['type'] ? '<' : '[') . trim($v['type']) . ($v['type'] ? '>' : ']') . ' ' . $this->commentCleanup($v['comment']),
                    ]);
                }, array_keys($args), array_values($args)),
                'options' => array_map(function($k, $v) {
                    return array_filter([
                        'name' => '--' . $k,
                        'description' => '(' . trim($v['type']) . ') ' . $this->commentCleanup($v['comment']),
                    ]);
                }, array_keys($options), array_values($options)),
            ]);
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
     * @param ReflectionMethod $reflection
     * @return string
     */
    protected function unformattedActionHelp(ReflectionMethod $reflection): string
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
