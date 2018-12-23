<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers;

use craft\console\actions\DynamicAction;

use craft\utilities\ClearCaches;
use craft\helpers\FileHelper;

use yii\console\Controller;

/**
 * Clear caches via the CLI
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class ClearCachesController extends Controller
{
    // Private Properties
    // =========================================================================

    /**
     * @var array
     */
    private $actions = [];

    /**
     * @var \Reflection
     */
    private $reflection;

    // Public Properties
    // =========================================================================

    public $allowAnonymous = [];

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        // Set up the actions array
        $cacheOptions = ClearCaches::cacheOptions();
        foreach ($cacheOptions as $cacheOption) {
            $this->actions[$cacheOption['key']] = [
                'class' => DynamicAction::class,
                'action' => $cacheOption['action'],
                'label' => $cacheOption['label'],
                'params' => $cacheOption['params'] ?? null,
                'controller' => $this,
            ];
        }
        // Set up a reflection for this class to handle closures
        $this->reflection = new \ReflectionMethod($this, 'dummyMethod');
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return $this->actions;
    }

    /**
     * Clear all caches
     *
     * @throws \yii\base\InvalidRouteException
     * @throws \yii\console\Exception
     */
    public function actionAll()
    {
        foreach ($this->actions as $id => $action) {
            $this->runAction($id);
        }
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function getActionMethodReflection($action)
    {
        if (\array_key_exists($action->id, $this->actions)) {
            if (is_string($this->actions[$action->id]['action'])) {
                return new \ReflectionMethod(FileHelper::class, 'clearDirectory');
            } else {
                if (is_array($this->actions[$action->id]['action'])) {
                    return new \ReflectionMethod(
                        $this->actions[$action->id]['action'][0],
                        $this->actions[$action->id]['action'][1]
                    );
                } else {
                    return $this->reflection;
                }
            }
        }

        return parent::getActionMethodReflection($action);
    }

    /**
     * @inheritdoc
     */
    public function getActionHelpSummary($action)
    {
        $help = parent::getActionHelpSummary($action);
        if (empty($help) && \array_key_exists($action->id, $this->actions)) {
            $help = $this->actions[$action->id]['label'];
        }

        return $help;
    }

    /**
     * @inheritdoc
     */
    public function getActionHelp($action)
    {
        $help = parent::getActionHelp($action);
        if (empty($help) && \array_key_exists($action->id, $this->actions)) {
            $help = $this->actions[$action->id]['label'];
        }

        return $help;
    }

    protected function dummyMethod()
    {
    }
}
