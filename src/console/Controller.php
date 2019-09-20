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
use yii\base\Action;
use yii\base\InvalidConfigException;
use yii\console\Controller as YiiController;
use yii\helpers\Inflector;

/**
 * Base console controller
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class Controller extends YiiController
{
    // Traits
    // =========================================================================

    use ControllerTrait;

    // Constants
    // =========================================================================

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
     *                 $query = Product::find();
     *                 if ($controller->type) {
     *                     $query->type(explode(',', $controller->type));
     *                 }
     *                 return $controller->saveElements($query);
     *             }
     *         ];
     *     }
     * );
     * ```
     */
    const EVENT_DEFINE_ACTIONS = 'defineActions';

    // Properties
    // =========================================================================

    /**
     * @var array Custom actions that should be available.
     * @see defineActions()
     */
    private $_actions;

    /**
     * @var \ReflectionFunction[] Memoized reflection objects
     * @see getActionMethodReflection()
     */
    private $_reflections = [];

    /**
     * @var string|null The active action ID.
     * @see runAction()
     */
    private $_actionId;

    // Public Methods
    // =========================================================================

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
    public function init()
    {
        parent::init();

        $this->_actions = [];
        foreach ($this->defineActions() as $id => $action) {
            if (is_callable($action)) {
                $action = ['action' => $action];
            }

            if (!isset($action['action'])) {
                throw new InvalidConfigException("Action '{$id}' is missing an 'action' key.");
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
    public function actions()
    {
        return ArrayHelper::getColumn($this->_actions, 'action');
    }

    /**
     * @inheritdoc
     */
    public function options($actionID)
    {
        $options = parent::options($actionID);

        if (isset($this->_actions[$actionID]['options'])) {
            $options = array_merge($options, array_keys($this->_actions[$actionID]['options']));
        }

        return $options;
    }

    /**
     * @inheritdoc
     */
    public function runAction($id, $params = [])
    {
        $this->_actionId = $id;
        $result = parent::runAction($id, $params);
        $this->_actionId = null;
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getActionHelpSummary($action)
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
    public function getActionHelp($action)
    {
        if (isset($this->_actions[$action->id])) {
            return $this->_actions[$action->id]['help'] ?? $this->_actions[$action->id]['helpSummary'] ?? '';
        }

        return parent::getActionHelp($action);
    }

    /**
     * @inheritdoc
     */
    public function getActionArgsHelp($action)
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
    public function getActionOptionsHelp($action)
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

    // Protected Methods
    // =========================================================================

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
     * @return \ReflectionMethod
     */
    protected function getActionMethodReflection($action)
    {
        if ($action instanceof CallableAction) {
            if (!isset($this->_reflections[$action->id])) {
                if (is_array($action->callable)) {
                    $this->_reflections[$action->id] = new \ReflectionMethod($action->callable[0], $action->callable[1]);
                } else {
                    $this->_reflections[$action->id] = new \ReflectionFunction($action->callable);
                }
            }
            return $this->_reflections[$action->id];
        }

        return parent::getActionMethodReflection($action);
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns whether the given option is defined by a custom action.
     *
     * @param string $name
     * @return bool
     */
    private function _isCustomOption(string $name): bool
    {
        return (
            $this->_actionId !== null &&
            isset($this->_actions[$this->_actionId]['options']) &&
            array_key_exists($name, $this->_actions[$this->_actionId]['options'])
        );
    }
}
