<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\test\console;

use Codeception\Stub;
use Craft;
use craft\console\Controller;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;

/**
 * Class ConsoleTest
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2.0
 */
class CommandTest
{
    public const STD_OUT = 'stdOut';
    public const STD_ERR = 'stderr';
    public const PROMPT = 'prompt';
    public const CONFIRM = 'confirm';
    public const SELECT = 'select';
    public const OUTPUT_COMMAND = 'outputCommand';

    /**
     * @var ConsoleTest
     */
    protected ConsoleTest $test;

    /**
     * @var string
     */
    protected string $command;

    /**
     * @var array
     */
    protected array $parameters;

    /**
     * @var bool
     */
    protected bool $ignoreStdout = false;

    /**
     * @var int
     */
    protected int $expectedExitCode;

    /**
     * @var bool
     */
    protected bool $hasExecuted = false;

    /**
     * @var array|CommandTestItem
     */
    protected CommandTestItem|array $eventChain = [];

    /**
     * @var int
     */
    protected int $currentIndex;

    /**
     * @var Controller
     */
    protected Controller $controller;

    /**
     * @var string
     */
    protected string $actionId;

    /**
     * @var int
     */
    protected int $desiredExitCode;

    /**
     * @var int
     */
    protected int $eventChainItemsHandled = 0;

    /**
     * CommandTest constructor.
     *
     * @param ConsoleTest $consoleTest
     * @param string $command
     * @param array $parameters
     * @param bool $ignoreStdOut
     * @throws InvalidConfigException
     */
    public function __construct(ConsoleTest $consoleTest, string $command, array $parameters = [], bool $ignoreStdOut = false)
    {
        $this->command = $command;
        $this->parameters = $parameters;
        $this->ignoreStdout = $ignoreStdOut;
        $this->test = $consoleTest;
        $this->setupController();
    }

    /**
     * @throws InvalidArgumentException
     */
    public function run(): void
    {
        if (!isset($this->desiredExitCode)) {
            throw new InvalidArgumentException('Please enter a desired exit code');
        }

        $exitCode = $this->controller->run($this->actionId, $this->parameters);
        $this->test::assertSame($this->desiredExitCode, $exitCode);
        $this->test::assertCount($this->eventChainItemsHandled, $this->eventChain);
    }

    /**
     * @param int $value
     * @return CommandTest
     */
    public function exitCode(int $value): CommandTest
    {
        $this->desiredExitCode = $value;
        return $this;
    }

    /**
     * @param iterable|string $desiredOutput
     * @return CommandTest
     */
    public function stdout(iterable|string $desiredOutput): CommandTest
    {
        return $this->addEventChainItem([
            'type' => self::STD_OUT,
            'desiredOutput' => $desiredOutput,
        ]);
    }

    /**
     * @param iterable|string $desiredOutput
     * @param bool $withScriptName
     * @return CommandTest
     */
    public function outputCommand(iterable|string $desiredOutput, bool $withScriptName = true): CommandTest
    {
        return $this->addEventChainItem([
            'type' => self::OUTPUT_COMMAND,
            'desiredOutput' => $desiredOutput,
            'withScriptName' => $withScriptName,
        ]);
    }

    /**
     * @param iterable|string $desiredOutput
     * @return CommandTest
     */
    public function stderr(iterable|string $desiredOutput): CommandTest
    {
        return $this->addEventChainItem([
            'type' => self::STD_ERR,
            'desiredOutput' => $desiredOutput,
        ]);
    }

    /**
     * @param string $prompt
     * @param mixed $returnValue
     * @param array $options
     * @return CommandTest
     */
    public function prompt(string $prompt, mixed $returnValue, array $options = []): CommandTest
    {
        return $this->addEventChainItem([
            'type' => self::PROMPT,
            'prompt' => $prompt,
            'options' => $options,
            'returnValue' => $returnValue,
        ]);
    }

    /**
     * @param string $message
     * @param mixed $returnValue
     * @param bool $default
     * @return CommandTest
     */
    public function confirm(string $message, mixed $returnValue, bool $default = false): CommandTest
    {
        return $this->addEventChainItem([
            'type' => self::CONFIRM,
            'message' => $message,
            'default' => $default,
            'returnValue' => $returnValue,
        ]);
    }

    /**
     * @param string $prompt
     * @param mixed $returnValue
     * @param array $options
     * @return CommandTest
     */
    public function select(string $prompt, mixed $returnValue, array $options = []): CommandTest
    {
        return $this->addEventChainItem([
            'type' => self::SELECT,
            'prompt' => $prompt,
            'options' => $options,
            'returnValue' => $returnValue,
        ]);
    }

    /**
     * @throws InvalidConfigException
     */
    protected function setupController(): void
    {
        $controllerArray = Craft::$app->createController($this->command);
        if (!$controllerArray) {
            throw new InvalidArgumentException('Invalid controller');
        }

        $controller = $controllerArray[0];
        if (!$controller instanceof Controller) {
            throw new InvalidArgumentException(
                'Invalid controller. Please ensure your controller extends: ' . Controller::class
            );
        }

        $actionId = $controllerArray[1];

        $stubController = Stub::construct(get_class($controller), [$controller->id, Craft::$app], [
            'stdOut' => $this->stdoutHandler(),
            'stderr' => $this->stderrHandler(),
            'prompt' => $this->promptHandler(),
            'confirm' => $this->confirmHandler(),
            'select' => $this->selectHandler(),
            'outputCommand' => $this->outputCommandHandler(),
        ]);

        $this->controller = $stubController;
        $this->actionId = $actionId;
    }

    /**
     * @return callable
     */
    protected function outputCommandHandler(): callable
    {
        return function($out, $withScriptName = true) {
            $nextItem = $this->runHandlerCheck($out, self::OUTPUT_COMMAND);
            $this->test::assertSame($nextItem->withScriptName, $withScriptName);
            if (is_string($nextItem->desiredOutput)) {
                $this->test::assertSame($nextItem->desiredOutput, $out);
            } else {
                $this->test::assertContains($out, $nextItem->desiredOutput);
            }
        };
    }

    /**
     * @return callable
     */
    protected function stdoutHandler(): callable
    {
        return function($out) {
            if (!$this->ignoreStdout) {
                $nextItem = $this->runHandlerCheck($out, self::STD_OUT);
                if (is_string($nextItem->desiredOutput)) {
                    $this->test::assertSame($nextItem->desiredOutput, $out);
                } else {
                    $this->test::assertContains($out, $nextItem->desiredOutput);
                }
            }
        };
    }

    /**
     * @return callable
     */
    protected function stderrHandler(): callable
    {
        return function($out) {
            $nextItem = $this->runHandlerCheck($out, self::STD_ERR);
            if (is_string($nextItem->desiredOutput)) {
                $this->test::assertSame($nextItem->desiredOutput, $out);
            } else {
                $this->test::assertContains($out, $nextItem->desiredOutput);
            }
        };
    }

    /**
     * @return callable
     */
    protected function promptHandler(): callable
    {
        return function($text, $options = []) {
            $nextItem = $this->runHandlerCheck('A prompt with value: ' . $text, self::PROMPT);
            $this->test::assertSame($nextItem->prompt, $text);
            $this->test::assertSame($nextItem->options, $options);
            return $nextItem->returnValue;
        };
    }

    /**
     * @return callable
     */
    protected function confirmHandler(): callable
    {
        return function($message, $default = false) {
            $nextItem = $this->runHandlerCheck('A confirm with value: ' . $message, self::CONFIRM);
            $this->test::assertSame($nextItem->message, $message);
            $this->test::assertSame($nextItem->default, $default);
            return $nextItem->returnValue;
        };
    }

    /**
     * @return callable
     */
    protected function selectHandler(): callable
    {
        return function($prompt, $options = []) {
            $nextItem = $this->runHandlerCheck('A select with value: ' . $prompt, self::SELECT);
            $this->test::assertSame($nextItem->prompt, $prompt);
            $this->test::assertSame($nextItem->options, $options);
            return $nextItem->returnValue;
        };
    }

    /**
     * @param string $out
     * @param string $type
     * @return CommandTestItem
     */
    protected function runHandlerCheck(string $out, string $type): CommandTestItem
    {
        $nextItem = $this->getNextItem();
        if (!$nextItem) {
            $this->test::fail("There are no more items however: $out was printed");
        }
        if ($nextItem->type !== $type) {
            $this->test::fail("A $type message was expected but $nextItem->type was given");
        }
        $this->eventChainItemsHandled++;
        return $nextItem;
    }

    /**
     * @return CommandTestItem|null
     */
    protected function getNextItem(): ?CommandTestItem
    {
        if (!isset($this->currentIndex)) {
            $this->currentIndex = 0;
        }

        if (count($this->eventChain) === $this->currentIndex) {
            return null;
        }

        $eventChainItem = $this->eventChain[$this->currentIndex];
        $this->currentIndex++;
        return $eventChainItem;
    }

    /**
     * @param array $config
     * @return CommandTest
     */
    protected function addEventChainItem(array $config): CommandTest
    {
        $this->eventChain[] = new CommandTestItem($config);
        return $this;
    }
}
