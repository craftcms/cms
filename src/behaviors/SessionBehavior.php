<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\behaviors;

use Craft;
use craft\helpers\Json;
use craft\web\Session;
use craft\web\View;
use yii\base\Behavior;
use yii\base\Exception;
use yii\web\AssetBundle;

/**
 * Extends \yii\web\Session to add support for setting the session folder and creating it if it doesn’t exist.
 *
 * @property Session $owner
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class SessionBehavior extends Behavior
{
    /**
     * @var string|null The session variable name used to store the authorization keys for the current session.
     * @see authorize()
     * @see deauthorize()
     * @see checkAuthorization()
     */
    public ?string $authAccessParam = null;

    /**
     * @var string the name of the flash key that stores asset bundle data
     */
    public string $assetBundleFlashKey = '__ab';

    /**
     * @var string the name of the flash key that stores JS data
     */
    public string $jsFlashKey = '__js';

    // Flash Data
    // -------------------------------------------------------------------------

    /**
     * Stores a notice in the user’s flash data.
     *
     * The message will be stored on the session, and can be retrieved by calling
     * [[getFlash()|`getFlash('notice')`]] or [[getAllFlashes()]].
     * Only one flash notice can be stored at a time.
     *
     * @param string $message The message
     * @param array $settings The control panel notification settings
     */
    public function setNotice(string $message, array $settings = []): void
    {
        if (Craft::$app->getRequest()->getIsCpRequest()) {
            $this->_setNotificationFlash('notice', $message, $settings + [
                    'icon' => 'info',
                    'iconLabel' => Craft::t('app', 'Notice'),
                ]);
            $this->owner->setFlash('cp-notice', $message);
        } else {
            $this->owner->setFlash('notice', $message);
        }
    }

    /**
     * Stores a success message in the user’s flash data.
     *
     * The message will be stored on the session, and can be retrieved by calling
     * [[getFlash()|`getFlash('notice')`]] or [[getAllFlashes()]].
     * Only one flash notice can be stored at a time.
     *
     * @param string $message The message
     * @param array $settings The control panel notification settings
     * @since 4.2.0
     */
    public function setSuccess(string $message, array $settings = []): void
    {
        if (Craft::$app->getRequest()->getIsCpRequest()) {
            $this->_setNotificationFlash('success', $message, $settings + [
                    'icon' => 'check',
                    'iconLabel' => Craft::t('app', 'Success'),
                ]);
        } else {
            // todo: switch to `success` in Craft 5
            $this->owner->setFlash('notice', $message);
        }
    }

    /**
     * Stores an error message in the user’s flash data.
     *
     * The message will be stored on the session, and can be retrieved by calling
     * [[getFlash()|`getFlash('error')`]] or [[getAllFlashes()]].
     * Only one flash error message can be stored at a time.
     *
     * @param string $message The message
     * @param array $settings The control panel notification settings
     */
    public function setError(string $message, array $settings = []): void
    {
        if (Craft::$app->getRequest()->getIsCpRequest()) {
            $this->_setNotificationFlash('error', $message, $settings + [
                    'icon' => 'alert',
                    'iconLabel' => Craft::t('app', 'Error'),
                ]);
        } else {
            $this->owner->setFlash('error', $message);
        }
    }

    /**
     * Retrieves a notice from the user’s flash data.
     *
     * @return string|null
     */
    public function getNotice(): ?string
    {
        if (Craft::$app->getRequest()->getIsCpRequest()) {
            return $this->_getNotificationFlashMessage('notice');
        }

        return $this->owner->getFlash('notice');
    }

    /**
     * Retrieves a success message from the user’s flash data.
     *
     * @return string|null
     * @since 4.2.0
     */
    public function getSuccess(): ?string
    {
        if (Craft::$app->getRequest()->getIsCpRequest()) {
            return $this->_getNotificationFlashMessage('success');
        }

        // todo: switch to `success` in Craft 5
        return $this->owner->getFlash('notice');
    }

    /**
     * Retrieves an error message from the user’s flash data.
     *
     * @return string|null
     */
    public function getError(): ?string
    {
        if (Craft::$app->getRequest()->getIsCpRequest()) {
            return $this->_getNotificationFlashMessage('error');
        }

        return $this->owner->getFlash('error');
    }

    private function _getNotificationFlashMessage(string $type)
    {
        return $this->owner->getFlash("cp-notification-$type")[0] ?? null;
    }

    private function _setNotificationFlash(string $type, string $message, array $settings = [])
    {
        $this->owner->setFlash("cp-notification-$type", [$message, $settings]);
    }

    /**
     * Queues up an asset bundle to be registered on a future request.
     *
     * Asset bundles that were queued with this method can be registered using [[getAssetBundleFlashes()]] or
     * [[\craft\web\View::getBodyHtml()]].
     *
     * @param string $name the class name of the asset bundle
     * @phpstan-param class-string<AssetBundle> $name
     * @param int|null $position if set, this forces a minimum position for javascript files.
     * @throws Exception if $name isn't an asset bundle class name
     * @see getAssetBundleFlashes()
     */
    public function addAssetBundleFlash(string $name, ?int $position = null): void
    {
        if (!is_subclass_of($name, AssetBundle::class)) {
            throw new Exception("$name is not an asset bundle");
        }

        $assetBundles = $this->getAssetBundleFlashes(false);
        $assetBundles[$name] = $position;
        $this->owner->setFlash($this->assetBundleFlashKey, $assetBundles);
    }

    /**
     * Returns the list of queued-up asset bundles in the session flash data.
     *
     * @param bool $delete Whether to delete the stored flashes. Defaults to `true`.
     * @return array The queued-up asset bundles.
     * @see addAssetBundleFlash()
     */
    public function getAssetBundleFlashes(bool $delete = false): array
    {
        return $this->owner->getFlash($this->assetBundleFlashKey, [], $delete);
    }

    /**
     * Stores JS in the user’s flash data.
     *
     * The JavaScript code will be stored on the session, and can be retrieved
     * by calling [[getJsFlashes()]] or [[\craft\web\View::getBodyHtml()]].
     *
     * @param string $js the JS code block to be registered
     * @param int $position the position at which the JS script tag should
     * be inserted in a page.
     * @param string|null $key the key that identifies the JS code block.
     * @see getJsFlashes()
     * @see View::registerJs()
     */
    public function addJsFlash(string $js, int $position = View::POS_READY, ?string $key = null): void
    {
        $scripts = $this->getJsFlashes();
        $scripts[] = [$js, $position, $key];
        $this->owner->setFlash($this->jsFlashKey, $scripts);
    }

    /**
     * Returns the stored JS flashes.
     *
     * @param bool $delete Whether to delete the stored flashes. Defaults to `true`.
     * @return array The stored JS flashes.
     * @see addJsFlash()
     */
    public function getJsFlashes(bool $delete = true): array
    {
        return $this->owner->getFlash($this->jsFlashKey, [], $delete);
    }

    /**
     * Broadcasts a message to all tabs opened to the control panel.
     *
     * @param string|array $message The message to broadcast.
     * @since 4.0.0
     */
    public function broadcastToJs(string|array $message): void
    {
        // This is a control panel-only feature
        if (!Craft::$app->getRequest()->getIsCpRequest()) {
            return;
        }

        $jsonMessage = Json::encode($message);
        $this->addJsFlash(<<<JS
if (Craft.broadcaster) {
    Craft.broadcaster.postMessage($jsonMessage);
}
JS
        );
    }

    // Session-Based Authorization
    // -------------------------------------------------------------------------

    /**
     * Authorizes the user to perform an action for the duration of the session.
     *
     * @param string $action
     */
    public function authorize(string $action): void
    {
        $access = $this->owner->get($this->authAccessParam, []);

        if (!in_array($action, $access, true)) {
            $access[] = $action;
            $this->owner->set($this->authAccessParam, $access);
        }
    }

    /**
     * Deauthorizes the user from performing an action.
     *
     * @param string $action
     */
    public function deauthorize(string $action): void
    {
        $access = $this->owner->get($this->authAccessParam, []);
        $index = array_search($action, $access, true);

        if ($index !== false) {
            array_splice($access, $index, 1);
            $this->owner->set($this->authAccessParam, $access);
        }
    }

    /**
     * Returns whether the user is authorized to perform an action.
     *
     * @param string $action
     * @return bool
     */
    public function checkAuthorization(string $action): bool
    {
        $access = $this->owner->get($this->authAccessParam, []);

        return in_array($action, $access, true);
    }
}
