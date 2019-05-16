<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\test\console;

use Codeception\Stub;
use craft\helpers\StringHelper;
use yii\base\InvalidArgumentException;
use yii\console\Controller;
use Craft;
use yii\base\InvalidConfigException;
use Closure;

/**
 * Class ConsoleTest
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.1
 */
class CommandTest
{
    // Public properties
    // =========================================================================

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
     * @var int
     */
    protected $expectedExitCode;

    /**
     * @var bool
     */
    protected $hasExecuted = false;

    /**
     * @var array|ConsoleTestItem
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
     * @var integer
     */
    protected $desiredExitCode;

    // Public Methods
    // =========================================================================

    /**
     * CommandTest constructor.
     *
     * @param ConsoleTest $consoleTest
     * @param string $command
     * @param array $parameters
     * @throws InvalidConfigException
     */
    public function __construct(ConsoleTest $consoleTest, string $command, array $parameters = [])
    {
        $this->command = $command;
        $this->parameters = $parameters;
        $this->test = $consoleTest;
        $this->setupController();
    }


    /**
     *
     */
    public function run()
    {
        if (!isset($this->desiredExitCode)) {
            throw new InvalidArgumentException('Please enter a desired exit code');
        }

        $exitCode = $this->controller->run($this->actionId);

        $this->test->assertSame($this->desiredExitCode, $exitCode);
    }

    /**
     * @param $value
     * @return CommandTest
     */
    public function exitCode($value) : CommandTest
    {
        $this->desiredExitCode = $value;
        return $this;
    }

    /**
     * @param string $desiredOutput
     * @return CommandTest
     */
    public function stdOut(string $desiredOutput) : CommandTest
    {
        $chainItem = new ConsoleTestItem([
            'type' => 'stdOut',
            'desiredOutput' => $desiredOutput
        ]);


        $this->addEventChainItem(
            $chainItem
        );

        return $this;
    }

    /**
     * @param string $desiredOutput
     * @return CommandTest
     */
    public function stderr(string $desiredOutput) : CommandTest
    {
        $chainItem = new ConsoleTestItem([
            'type' => 'stderr',
            'desiredOutput' => $desiredOutput
        ]);

        $this->addEventChainItem(
            $chainItem
        );

        return $this;
    }

    /**
     * @param string $prompt
     * @param array $options
     * @return CommandTest
     */
    public function prompt(string $prompt, array $options = []) : CommandTest
    {
        $chainItem = new ConsoleTestItem([
            'type' => 'prompt',
            'prompt' => $prompt,
            'options' => $options
        ]);

        $this->addEventChainItem(
            $chainItem
        );

        return $this;
    }

    // Protected Methods
    // =========================================================================

    /**
     * @throws InvalidConfigException
     */
    protected function setupController()
    {
        $parts = StringHelper::split($this->command, '/');
        $controllerId = $parts[0];
        $actionId = $parts[1];

        $controller = Craft::$app->createControllerByID($controllerId);
        if (!$controller instanceof Controller) {
            throw new InvalidArgumentException('Invalid controller');
        }

        $stubController = Stub::construct(get_class($controller), [$controllerId, \Craft::$app], [
            'stdOut' => $this->stdOutHandler(),
            'stderr' => function() {},
            'prompt' => function() {},
            'confirm' => function() {},
            'select' => function() {}
            ]);

        $this->controller = $stubController;
        $this->actionId = $actionId;
    }

    /**
     * @return Closure
     */
    protected function stdOutHandler()
    {
        return function ($out) {
            $nextItem = $this->getNextItem();

            if (!$nextItem) {
                $this->test::fail("There are no more items however: $out was printed");
            }

            $this->test::assertSame(
                $nextItem->desiredOutput,
                $out
            );
        };
    }

    /**
     * @return ConsoleTestItem|null
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

        $this->currentIndex ++;

        return $eventChainItem;
    }

    /**
     * @param ConsoleTestItem $item
     */
    protected function addEventChainItem(ConsoleTestItem $item)
    {
        $this->eventChain[] = $item;
    }
}
