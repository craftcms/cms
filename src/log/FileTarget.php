<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\log;

use Craft;
use craft\helpers\ArrayHelper;
use yii\helpers\VarDumper;
use yii\web\Request;

/**
 * Class FileTarget
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class FileTarget extends \yii\log\FileTarget
{
    /**
     * @var bool Whether the user IP should be included in the default log prefix.
     * @since 3.0.25
     * @see prefix
     */
    public $includeUserIp = false;

    /**
     * @inheritdoc
     */
    public function getMessagePrefix($message)
    {
        if ($this->prefix !== null) {
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

        /* @var $user \yii\web\User */
        $user = Craft::$app->has('user', true) ? Craft::$app->get('user') : null;
        if ($user && ($identity = $user->getIdentity(false))) {
            $userID = $identity->getId();
        } else {
            $userID = '-';
        }

        /* @var $session \yii\web\Session */
        $session = Craft::$app->has('session', true) ? Craft::$app->get('session') : null;
        $sessionID = $session && $session->getIsActive() ? $session->getId() : '-';

        return "[$ip][$userID][$sessionID]";
    }

    /**
     * @inheritdoc
     */
    protected function getContextMessage()
    {
        $context = ArrayHelper::filter($GLOBALS, $this->logVars);
        $result = [];
        $security = Craft::$app->getSecurity();

        foreach ($context as $key => $value) {
            $value = $security->redactIfSensitive($key, $value);
            $result[] = "\${$key} = " . VarDumper::dumpAsString($value);
        }

        return implode("\n\n", $result);
    }
}
