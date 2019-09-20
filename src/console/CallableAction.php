<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console;

use Craft;
use yii\base\Action;
use yii\console\Exception;

/**
 * Class CallableAction
 *
 * @since 3.2
 */
class CallableAction extends Action
{
    /**
     * @var callable The action callable
     */
    public $callable;

    /**
     * Runs this action with the specified parameters.
     * This method is mainly invoked by the controller.
     *
     * @param array $params action parameters
     * @return mixed the result of the action
     */
    public function runWithParams($params)
    {
        $args = $this->_bindActionParams($params);
        Craft::debug('Running callable action', __METHOD__);
        if (Craft::$app->requestedParams === null) {
            Craft::$app->requestedParams = $args;
        }

        return call_user_func_array($this->callable, $args);
    }

    /**
     * Binds the parameters to the action.
     *
     * @param array $params the parameters to be bound to the action
     * @return array the valid parameters that the action can run with.
     * @throws Exception if there are unknown options or missing arguments
     */
    private function _bindActionParams($params): array
    {
        if (is_array($this->callable)) {
            $method = new \ReflectionMethod($this->callable[0], $this->callable[1]);
        } else {
            $method = new \ReflectionFunction($this->callable);
        }

        $args = array_values($params);

        $missing = [];
        foreach ($method->getParameters() as $i => $param) {
            if ($param->isArray() && isset($args[$i])) {
                $args[$i] = $args[$i] === '' ? [] : preg_split('/\s*,\s*/', $args[$i]);
            }
            if (!isset($args[$i])) {
                if ($param->isDefaultValueAvailable()) {
                    $args[$i] = $param->getDefaultValue();
                } else {
                    $missing[] = $param->getName();
                }
            }
        }

        if (!empty($missing)) {
            throw new Exception(Craft::t('yii', 'Missing required arguments: {params}', ['params' => implode(', ', $missing)]));
        }

        return $args;
    }
}
