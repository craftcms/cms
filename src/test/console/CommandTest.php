<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\test\console;

use Closure;
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
    const STD_OUT = 'stdOut';
    const STD_ERR = 'stderr';
    const PROMPT = 'prompt';
    const CONFIRM = 'confirm';
    const SELECT = 'select';
    const OUTPUT_COMMAND = 'outputCommand';

    /**
     * @var ConsoleTest
     */
    protected $test;

    /**
     * @var string
     */
    protected $command;

    /**
     * @var array
     */
    protected $parameters;

    /**
     * @var bool
     */
    protected $ignoreStdOut = false;

    /**
     * @var int
     */
    protected $expectedExitCode;

    /**
     * @var bool
     */
    protected $hasExecuted = false;

    /**
     * @var array|CommandTestItem
     */
    protected $eventChain = [];

    /**
     * @var integer
     */
    protected $currentIndex;

    /**
     * @var Controller
     */
    protected $controller;

    /**
     * @var string
     */
    protected $actionId;

    /**
     * @var int
     */
    protected $desiredExitCode;

    /**
     * @var int
     */
    protected $eventChainItemsHandled = 0;

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
        $this->ignoreStdOut = $ignoreStdOut;
        $this->test = $consoleTest;
        $this->setupController();
    }

    /**
     * @throws InvalidArgumentException
     */
    public function run()
    {
        if (!isset($this->desiredExitCode)) {
            throw new InvalidArgumentException('Please enter a desired exit code');
        }

        $exitCode = $this->controller->run($this->actionId, $this->parameters);
        $test = $this->test;
        $test::assertSame($this->desiredExitCode, $exitCode);
        $test::assertCount($this->eventChainItemsHandled, $this->eventChain);
    }

    /**
     * @param $value
     * @return CommandTest
     */
    public function exitCode($value): CommandTest
    {
        $this->desiredExitCode = $value;
        return $this;
    }

    /**
     * @param string $desiredOutput
     * @return CommandTest
     */
    public function stdOut(string $desiredOutput): CommandTest
    {
        return $this->addEventChainItem([
            'type' => self::STD_OUT,
            'desiredOutput' => $desiredOutput
        ]);
    }

    /**
     * @param string $desiredOutput
     * @param bool $withScriptName
     * @return CommandTest
     */
    public function outputCommand(string $desiredOutput, bool $withScriptName = true): CommandTest
    {
        return $this->addEventChainItem([
            'type' => self::OUTPUT_COMMAND,
            'desiredOutput' => $desiredOutput,
            'withScriptName' => $withScriptName
        ]);
    }

    /**
     * @param string $desiredOutput
     * @return CommandTest
     */
    public function stderr(string $desiredOutput): CommandTest
    {
        return $this->addEventChainItem([
            'type' => self::STD_ERR,
            'desiredOutput' => $desiredOutput
        ]);
    }

    /**
     * @param string $prompt
     * @param $returnValue
     * @param array $options
     * @return CommandTest
     */
    public function prompt(string $prompt, $returnValue, array $options = []): CommandTest
    {
        return $this->addEventChainItem([
            'type' => self::PROMPT,
            'prompt' => $prompt,
            'options' => $options,
            'returnValue' => $returnValue
        ]);
    }

    /**
     * @param string $message
     * @param $returnValue
     * @param bool $default
     * @return CommandTest
     */
    public function confirm(string $message, $returnValue, bool $default = false): CommandTest
    {
        return $this->addEventChainItem([
            'type' => self::CONFIRM,
            'message' => $message,
            'default' => $default,
            'returnValue' => $returnValue
        ]);
    }

    /**
     * @param $prompt
     * @param $returnValue
     * @param array $options
     * @return CommandTest
     */
    public function select(string $prompt, $returnValue, $options = []): CommandTest
    {
        return $this->addEventChainItem([
            'type' => self::SELECT,
            'prompt' => $prompt,
            'options' => $options,
            'returnValue' => $returnValue
        ]);
    }

    /**
     * @throws InvalidConfigException
     */
    protected function setupController()
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
            'stdOut' => $this->stdOutHandler(),
            'stderr' => $this->stderrHandler(),
            'prompt' => $this->promptHandler(),
            'confirm' => $this->confirmHandler(),
            'select' => $this->selectHandler(),
            'outputCommand' => $this->outputCommandHandler()
        ]);

        $this->controller = $stubController;
        $this->actionId = $actionId;
    }

    /**
     * @return Closure
     */
    protected function outputCommandHandler(): Closure
    {
        return function($out, $withScriptName = true) {
            $nextItem = $this->runHandlerCheck($out, self::OUTPUT_COMMAND);
            $test = $this->test;
            $test::assertSame(
                $nextItem->withScriptName,
                $withScriptName
            );
            $test::assertSame(
                $nextItem->desiredOutput,
                $out
            );
        };
    }

    /**
     * @return Closure
     */
    protected function stdOutHandler(): Closure
    {
        return function($out) {
            if (!$this->ignoreStdOut) {
                $nextItem = $this->runHandlerCheck($out, self::STD_OUT);
                $test = $this->test;
                $test::assertSame($nextItem->desiredOutput, $out);
            }
        };
    }

    /**
     * @return Closure
     */
    protected function stderrHandler(): Closure
    {
        return function($out) {
            $nextItem = $this->runHandlerCheck($out, self::STD_ERR);
            $test = $this->test;
            $test::assertSame(
                $nextItem->desiredOutput,
                $out
            );
        };
    }

    /**
     * @return Closure
     */
    protected function promptHandler(): Closure
    {
        return function($text, $options = []) {
            $nextItem = $this->runHandlerCheck('A prompt with value: ' . $text, self::PROMPT);
            $test = $this->test;
            $test::assertSame(
                $nextItem->prompt,
                $text
            );
            $test::assertSame(
                $nextItem->options,
                $options
            );
            return $nextItem->returnValue;
        };
    }

    /**
     * @return Closure
     */
    protected function confirmHandler(): Closure
    {
        return function($message, $default = false) {
            $nextItem = $this->runHandlerCheck('A confirm with value: ' . $message, self::CONFIRM);
            $test = $this->test;
            $test::assertSame(
                $nextItem->message,
                $message
            );
            $test::assertSame(
                $nextItem->default,
                $default
            );
            return $nextItem->returnValue;
        };
    }

    /**
     * @return Closure
     */
    protected function selectHandler(): Closure
    {
        return function($prompt, $options = []) {
            $nextItem = $this->runHandlerCheck('A select with value: ' . $prompt, self::SELECT);
            $test = $this->test;
            $test::assertSame(
                $nextItem->prompt,
                $prompt
            );
            $test::assertSame(
                $nextItem->options,
                $options
            );
            return $nextItem->returnValue;
        };
    }

    /**
     * @param $out
     * @param $type
     *
     * @return CommandTestItem
     */
    protected function runHandlerCheck($out, $type): CommandTestItem
    {
        $nextItem = $this->getNextItem();
        $test = $this->test;
        if (!$nextItem) {
            $test::fail("There are no more items however: $out was printed");
        }
        if ($nextItem->type !== $type) {
            $test::fail("A $type message was expected but $nextItem->type was given");
        }
        $this->eventChainItemsHandled++;
        return $nextItem;
    }

    /**
     * @return CommandTestItem|null
     */
    protected function getNextItem()
    {
        if ($this->currentIndex === null) {
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
     *
     * @return CommandTest
     */
    protected function addEventChainItem(array $config): CommandTest
    {
        $this->eventChain[] = new CommandTestItem($config);

        return $this;
    }
}
