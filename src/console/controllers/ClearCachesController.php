<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers;

use craft\console\actions\ClearCacheAction;
use craft\helpers\Console;
use craft\helpers\FileHelper;
use craft\utilities\ClearCaches;
use yii\base\InvalidRouteException;
use yii\console\Controller;
use yii\console\Exception;
use yii\console\ExitCode;

/**
 * Clear caches via the CLI
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.37
 */
class ClearCachesController extends Controller
{
    // Properties
    // =========================================================================

    /**
     * @var array
     */
    private $_actions = [];

    /**
     * @var \Reflection
     */
    private $_dummyReflection;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        // Set up the actions array
        $cacheOptions = ClearCaches::cacheOptions();
        foreach ($cacheOptions as $cacheOption) {
            $this->_actions[$cacheOption['key']] = [
                'class' => ClearCacheAction::class,
                'action' => $cacheOption['action'],
                'label' => $cacheOption['label'],
                'params' => $cacheOption['params'] ?? null,
                'controller' => $this,
            ];
        }
        // Set up a reflection for this class to handle closures
        $this->_dummyReflection = new \ReflectionMethod($this, 'dummyMethod');
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return $this->_actions;
    }

    /**
     * Lists the caches that can be cleared.
     *
     * @return int
     */
    public function actionIndex(): int
    {
        $this->stdout("The following caches can be cleared:\n\n", Console::FG_YELLOW);

        $lengths = [];
        foreach ($this->_actions as $action) {
            $lengths[] = strlen($action['label']);
        }
        $maxLength = max($lengths);

        foreach ($this->_actions as $id => $action) {
            $this->stdout('- ');
            $this->stdout(str_pad($id, $maxLength, ' '), Console::FG_YELLOW);
            $this->stdout('  ' . $action['label'] . PHP_EOL);
        }

        $this->stdout(PHP_EOL);
        return ExitCode::OK;
    }

    /**
     * Clear all caches
     *
     * @return int
     * @throws InvalidRouteException
     * @throws Exception
     */
    public function actionAll(): int
    {
        foreach ($this->_actions as $id => $action) {
            $this->runAction($id);
        }
        return ExitCode::OK;
    }

    /**
     * @inheritdoc
     */
    public function getActionHelpSummary($action)
    {
        $help = parent::getActionHelpSummary($action);
        if (empty($help) && array_key_exists($action->id, $this->_actions)) {
            $help = $this->_actions[$action->id]['label'];
        }

        return $help;
    }

    /**
     * @inheritdoc
     */
    public function getActionHelp($action)
    {
        $help = parent::getActionHelp($action);
        if (empty($help) && array_key_exists($action->id, $this->_actions)) {
            $help = $this->_actions[$action->id]['label'];
        }

        return $help;
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function getActionMethodReflection($action)
    {
        if (array_key_exists($action->id, $this->_actions)) {
            if (is_string($this->_actions[$action->id]['action'])) {
                return new \ReflectionMethod(FileHelper::class, 'clearDirectory');
            } else {
                if (is_array($this->_actions[$action->id]['action'])) {
                    return new \ReflectionMethod(
                        $this->_actions[$action->id]['action'][0],
                        $this->_actions[$action->id]['action'][1]
                    );
                } else {
                    return $this->_dummyReflection;
                }
            }
        }

        return parent::getActionMethodReflection($action);
    }

    protected function dummyMethod()
    {
    }
}
