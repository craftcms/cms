<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use Craft;
use craft\helpers\ArrayHelper;
use Throwable;
use yii\base\InvalidConfigException;
use yii\helpers\VarDumper;
use yii\log\Target;
use yii\web\Request;
use yii\web\Session;

/**
 * LogTargetTrait implements the common methods and properties for log target classes.
 *
 * @mixin Target
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.1.23
 */
trait LogTargetTrait
{
    /**
     * @var bool Whether the user IP should be included in the default log prefix.
     * @since 3.0.25
     * @see Target::$prefix
     */
    public bool $includeUserIp = false;

    /**
     * Returns a string to be prefixed to the given message.
     * If [[Target::$prefix]] is configured it will return the result of the callback.
     *
     * @param array $message the message being exported.
     * The message structure follows that in [[\yii\log\Logger::$messages]].
     * @return string the prefix string
     * @throws InvalidConfigException
     * @throws Throwable
     * @see Target::getMessagePrefix()
     */
    public function getMessagePrefix($message): string
    {
        if (isset($this->prefix)) {
            return call_user_func($this->prefix, $message);
        }

        if (Craft::$app === null) {
            return '';
        }

        if ($this->includeUserIp) {
            $request = Craft::$app->getRequest();
            $ip = $request instanceof Request ? $request->getUserIP() : '-';
        } else {
            $ip = '-';
        }

        $user = Craft::$app->has('user', true) ? Craft::$app->getUser() : null;
        if ($user && ($identity = $user->getIdentity(false))) {
            $userID = $identity->getId();
        } else {
            $userID = '-';
        }

        $session = Craft::$app->has('session', true) ? Craft::$app->get('session') : null;
        /** @var Session|null $session */
        $sessionID = $session && $session->getIsActive() ? $session->getId() : '-';

        return "[$ip][$userID][$sessionID]";
    }

    /**
     * Generates the context information to be logged.
     *
     * @return string the context information. If an empty string, it means no context information.
     * @see Target::getContextMessage()
     */
    protected function getContextMessage(): string
    {
        $result = [];

        if (
            ($postPos = array_search('_POST', $this->logVars)) !== false &&
            empty($GLOBALS['_POST']) &&
            !empty($body = file_get_contents('php://input'))
        ) {
            // Log the raw request body instead
            $logVars = array_merge($this->logVars);
            array_splice($logVars, $postPos, 1);
            $result[] = "Request body: $body";
        } else {
            $logVars = $this->logVars;
        }

        $context = ArrayHelper::filter($GLOBALS, $logVars);

        // Workaround for codeception testing until these gets addressed:
        // https://github.com/yiisoft/yii-core/issues/49
        // https://github.com/yiisoft/yii2/issues/15847
        if (Craft::$app) {
            $security = Craft::$app->getSecurity();

            foreach ($context as $key => $value) {
                $value = $security->redactIfSensitive($key, $value);
                $result[] = "\$$key = " . VarDumper::dumpAsString($value);
            }
        }

        return implode("\n\n", $result);
    }
}
