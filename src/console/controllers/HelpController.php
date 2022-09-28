<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers;

use Craft;
use craft\helpers\Json;
use yii\console\Controller;
use yii\console\controllers\HelpController as BaseHelpController;
use yii\console\Exception;
use yii\helpers\Console;
use yii\helpers\Inflector;

/**
 * @inerhitdoc
 */
class HelpController extends BaseHelpController
{
    /**
     * @var bool Should the commands help be returned in JSON format?
     */
    public bool $asJson = false;

    /**
     * @var array The base options provided by the yii\console\Controller
     */
    protected $baseOptions = [];

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->baseOptions = [];
        // Factor out the base options from the yii\console\Controller
        foreach (parent::options('') as $option) {
            $option = Inflector::camel2id($option, '-', true);
            $this->baseOptions[$option] = $option;
        }
    }

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
    public function optionAliases()
    {
        return ['j' => 'asJson'];
    }

    /**
     * @inerhitdoc
     */
    public function actionIndex($command = null)
    {
        // If they don't want JSON, let the parent do its thing
        if (!$this->asJson) {
            parent::actionIndex($command);
            return;
        }
        // Get the command info to output
        if ($command !== null) {
            $commands = $this->commandInfo($command);
        } else {
            $commands = $this->allCommandsInfo();
        }

        // Send the commands encoded as JSON to stdout
        $jsonOptions = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | (YII_DEBUG ? JSON_PRETTY_PRINT : 0);
        $this->stdout(Json::encode($commands, $jsonOptions));
    }

    /**
     * Return an array of information on the passed in CLI $command
     *
     * @param string $command
     * @return array
     * @throws Exception
     * @throws \yii\base\InvalidConfigException
     */
    protected function commandInfo(string $command): array
    {
        $commandInfo = [];

        $result = Craft::$app->createController($command);
        if ($result === false) {
            $name = $this->ansiFormat($command, Console::FG_YELLOW);
            throw new Exception("No help for unknown command \"$name\".");
        }

        list($controller, $actionID) = $result;
        $actions = $this->getActions($controller);
        if ($actionID !== '' || count($actions) === 1 && $actions[0] === $controller->defaultAction) {
            // Anonymous function to clean up descriptions coming from Yii
            $cleanUpDescription = fn(string $description): string => trim(
                    preg_replace('/\s\s+/', ' ',
                        preg_replace('/\\n/', ' ', $description)
                    )
                );
            // Try/catch in case an exception is thrown during reflection
            try {
                $action = $controller->createAction($actionID);
                // Get the command descrition, args, and options
                $description = $this->getUnformattedActionHelp($controller->getActionMethodReflection($action));
                $args = $controller->getActionArgsHelp($action);
                $options = $controller->getActionOptionsHelp($action);
                // Exclude any options coming from the base yii\console\Controller
                $options = array_diff_key($options, $this->baseOptions);

                return array_filter([
                    'name' => $command,
                    'description' => $cleanUpDescription($description),
                    'args' => array_map(fn(array $k, array $v): array => array_filter([
                        'name' => $k,
                        'description' => ($v['type'] ? '<' : '[') . trim($v['type']) . ($v['type'] ? '>' : ']') . ' ' . $cleanUpDescription($v['comment']),
                    ]), array_keys($args), array_values($args)),
                    'options' => array_map(fn(array $k, array $v): array => array_filter([
                        'name' => '--' . $k,
                        'description' => '(' . trim($v['type']) . ') ' . $cleanUpDescription($v['comment']),
                    ]), array_keys($options), array_values($options)),
                ]);
            } catch (\Throwable $e) {
                $this->stderr($e->getMessage());
            }
        }

        return $commandInfo;
    }

    /**
     * Return an array of information for every CLI command
     *
     * @return array
     * @throws Exception
     * @throws \yii\base\InvalidConfigException
     */
    protected function allCommandsInfo(): array
    {
        $commandNames = [];
        // Get all of the command names
        foreach ($this->getCommandDescriptions() as $command => $description) {
            $result = Craft::$app->createController($command);
            /** @var $controller Controller */
            list($controller, $actionID) = $result;
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
     * @param \Reflector $reflection
     * @return string
     */
    protected function getUnformattedActionHelp($reflection)
    {
        $comment = strtr(trim(preg_replace('/^\s*\**( |\t)?/m', '', trim($reflection->getDocComment(), '/'))), "\r", '');
        if (preg_match('/^\s*@\w+/m', $comment, $matches, PREG_OFFSET_CAPTURE)) {
            $comment = trim(substr($comment, 0, $matches[0][1]));
        }

        return rtrim($comment);
    }
}
