<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\behaviors;

use craft\web\Session;
use craft\web\View;
use CraftCms\Cms\Auth\SessionAuth;
use CraftCms\Cms\View\Enums\Position;
use yii\base\Behavior;
use yii\base\Exception;
use yii\web\AssetBundle;
use function CraftCms\Cms\t;

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
        if (request()->isCpRequest()) {
            $this->_setNotificationFlash('notice', $message, $settings + [
                    'icon' => 'info',
                    'iconLabel' => t('Notice'),
                ]);
        } else {
            session()->flash('notice', $message);
        }
    }

    /**
     * Stores a success message in the user’s flash data.
     *
     * The message will be stored on the session, and can be retrieved by calling
     * [[getFlash()|`getFlash('success')`]] or [[getAllFlashes()]].
     * Only one flash success message can be stored at a time.
     *
     * @param string $message The message
     * @param array $settings The control panel notification settings
     * @since 4.2.0
     */
    public function setSuccess(string $message, array $settings = []): void
    {
        if (request()->isCpRequest()) {
            $this->_setNotificationFlash('success', $message, $settings + [
                    'icon' => 'check',
                    'iconLabel' => t('Success'),
                ]);
        } else {
            session()->flash('success', $message);
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
        if (request()->isCpRequest()) {
            $this->_setNotificationFlash('error', $message, $settings + [
                    'icon' => 'alert',
                    'iconLabel' => t('Error'),
                ]);
        } else {
            session()->flash('error', $message);
        }
    }

    /**
     * Retrieves a notice from the user’s flash data.
     *
     * @return string|null
     */
    public function getNotice(): ?string
    {
        if (request()->isCpRequest()) {
            return $this->_getNotificationFlashMessage('notice');
        }

        return session()->get('notice');
    }

    /**
     * Retrieves a success message from the user’s flash data.
     *
     * @return string|null
     * @since 4.2.0
     */
    public function getSuccess(): ?string
    {
        if (request()->isCpRequest()) {
            return $this->_getNotificationFlashMessage('success');
        }

        return session()->get('success');
    }

    /**
     * Retrieves an error message from the user’s flash data.
     *
     * @return string|null
     */
    public function getError(): ?string
    {
        if (request()->isCpRequest()) {
            return $this->_getNotificationFlashMessage('error');
        }

        return session()->get('error');
    }

    private function _getNotificationFlashMessage(string $type)
    {
        return session()->get("cp-notification-$type")[0] ?? null;
    }

    private function _setNotificationFlash(string $type, string $message, array $settings = [])
    {
        session()->flash("cp-notification-$type", [$message, $settings]);
    }

    /**
     * Queues up an asset bundle to be registered on a future request.
     *
     * Asset bundles that were queued with this method can be registered using [[getAssetBundleFlashes()]] or
     * [[\craft\web\View::getBodyHtml()]].
     *
     * @param class-string<AssetBundle> $name the class name of the asset bundle
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
        session()->flash($this->assetBundleFlashKey, $assetBundles);
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
        if ($delete) {
            return session()->pull($this->assetBundleFlashKey, []);
        }

        return session()->get($this->assetBundleFlashKey, []);
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
     *
     * @see getJsFlashes()
     * @see View::registerJs()
     * @deprecated 6.0.0 use {@see \Illuminate\Support\Facades\Session::flashJs()} instead.
     */
    public function addJsFlash(string $js, int $position = View::POS_READY, ?string $key = null): void
    {
        session()->flashJs($js, Position::tryFrom($position) ?? Position::Head, $key);
    }

    /**
     * Returns the stored JS flashes.
     *
     * @param bool $delete Whether to delete the stored flashes. Defaults to `true`.
     *
     * @return array The stored JS flashes.
     * @see addJsFlash()
     * @deprecated 6.0.0 use {@see \Illuminate\Support\Facades\Session::getJs()} instead.
     */
    public function getJsFlashes(bool $delete = true): array
    {
        return session()->getJs($delete);
    }

    /**
     * Broadcasts a message to all tabs opened to the control panel.
     *
     * @param string|array $message The message to broadcast.
     * @since 4.0.0
     * @deprecated 6.0.0 use {@see \Illuminate\Support\Facades\Session::broadcastToJs()} instead.
     */
    public function broadcastToJs(string|array $message): void
    {
        session()->broadcastToJs($message);
    }

    // Session-Based Authorization
    // -------------------------------------------------------------------------

    /**
     * Authorizes the user to perform an action for the duration of the session.
     *
     * @param string $action
     *
     * @deprecated 6.0.0 use {@see SessionAuth::authorize} instead.
     */
    public function authorize(string $action): void
    {
        SessionAuth::authorize($action);
    }

    /**
     * Deauthorizes the user from performing an action.
     *
     * @param string $action
     *
     * @deprecated 6.0.0 use {@see SessionAuth::deauthorize} instead.
     */
    public function deauthorize(string $action): void
    {
        SessionAuth::deauthorize($action);
    }

    /**
     * Returns whether the user is authorized to perform an action.
     *
     * @param string $action
     *
     * @return bool
     * @deprecated 6.0.0 use {@see SessionAuth::checkAuthorization} instead.
     */
    public function checkAuthorization(string $action): bool
    {
        return SessionAuth::checkAuthorization($action);
    }
}
