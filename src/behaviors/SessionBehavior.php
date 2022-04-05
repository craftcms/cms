<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\behaviors;

use Craft;
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
    public $authAccessParam;

    /**
     * @var string the name of the flash key that stores asset bundle data
     */
    public $assetBundleFlashKey = '__ab';

    /**
     * @var string the name of the flash key that stores JS data
     */
    public $jsFlashKey = '__js';

    // Flash Data
    // -------------------------------------------------------------------------

    /**
     * Stores a notice in the user’s flash data.
     *
     * The message will be stored on the session, and can be retrieved by calling
     * [[getFlash()|`getFlash('notice')`]] or [[getAllFlashes()]].
     * Only one flash notice can be stored at a time.
     *
     * @param string $message The message.
     */
    public function setNotice(string $message)
    {
        if (Craft::$app->getRequest()->getIsCpRequest()) {
            $this->owner->setFlash('cp-notice', $message);
        } else {
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
     * @param string $message The message.
     */
    public function setError(string $message)
    {
        if (Craft::$app->getRequest()->getIsCpRequest()) {
            $this->owner->setFlash('cp-error', $message);
        } else {
            $this->owner->setFlash('error', $message);
        }
    }

    /**
     * Queues up an asset bundle to be registered on a future request.
     *
     * Asset bundles that were queued with this method can be registered using [[getAssetBundleFlashes()]] or
     * [[\craft\web\View::getBodyHtml()]].
     *
     * @param string $name the class name of the asset bundle (without the leading backslash)
     * @param integer|null $position if set, this forces a minimum position for javascript files.
     * @throws Exception if $name isn't an asset bundle class name
     * @see getAssetBundleFlashes()
     */
    public function addAssetBundleFlash(string $name, int $position = null)
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
     * @param integer $position the position at which the JS script tag should
     * be inserted in a page.
     * @param string|null $key the key that identifies the JS code block.
     * @see getJsFlashes()
     * @see View::registerJs()
     */
    public function addJsFlash(string $js, int $position = View::POS_READY, string $key = null)
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

    // Session-Based Authorization
    // -------------------------------------------------------------------------

    /**
     * Authorizes the user to perform an action for the duration of the session.
     *
     * @param string $action
     */
    public function authorize(string $action)
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
    public function deauthorize(string $action)
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
