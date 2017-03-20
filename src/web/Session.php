<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\web;

use Craft;
use yii\base\Exception;
use yii\web\AssetBundle;

/**
 * Extends \yii\web\Session to add support for setting the session folder and creating it if it doesn’t exist.
 *
 * An instance of the HttpSession service is globally accessible in Craft via [[Application::httpSession `Craft::$app->getSession()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Session extends \yii\web\Session
{
    // Properties
    // =========================================================================

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

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        // Set the state-based property names
        $stateKeyPrefix = md5('Craft.'.get_class($this).'.'.Craft::$app->id);
        $config['flashParam'] = $stateKeyPrefix.'__flash';
        $config['authAccessParam'] = $stateKeyPrefix.'__auth_access';

        // Set the session name
        $this->setName(Craft::$app->getConfig()->getGeneral()->phpSessionName);

        // Set the default session cookie params
        $this->setCookieParams(Craft::cookieConfig());

        parent::__construct($config);
    }

    // Flash Data
    // -------------------------------------------------------------------------

    /**
     * Stores a notice in the user’s flash data.
     *
     * The message will be stored on the session, and can be retrieved by calling
     * [[getFlash() `getFlash('notice')`]] or [[getAllFlashes()]].
     *
     * Only one flash notice can be stored at a time.
     *
     * @param string $message The message.
     *
     * @return void
     */
    public function setNotice(string $message)
    {
        $this->setFlash('notice', $message);
    }

    /**
     * Stores an error message in the user’s flash data.
     *
     * The message will be stored on the session, and can be retrieved by calling
     * [[getFlash() `getFlash('error')`]] or [[getAllFlashes()]].
     *
     * Only one flash error message can be stored at a time.
     *
     * @param string $message The message.
     *
     * @return void
     */
    public function setError(string $message)
    {
        $this->setFlash('error', $message);
    }

    /**
     * Queues up an asset bundle to be registered on a future request.
     *
     * Asset bundles that were queued with this method can be registered using [[getAssetBundleFlashes()]] or
     * [[\craft\web\View::getBodyHtml()]].
     *
     * @param string       $name     the class name of the asset bundle (without the leading backslash)
     * @param integer|null $position if set, this forces a minimum position for javascript files.
     *
     * @return void
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
        $this->setFlash($this->assetBundleFlashKey, $assetBundles);
    }

    /**
     * Returns the list of queued-up asset bundles in the session flash data.
     *
     * @param bool $delete Whether to delete the stored flashes. Defaults to `true`.
     *
     * @return array The queued-up asset bundles.
     * @see addAssetBundleFlash()
     */
    public function getAssetBundleFlashes(bool $delete = false): array
    {
        return $this->getFlash($this->assetBundleFlashKey, [], $delete);
    }

    /**
     * Stores JS in the user’s flash data.
     *
     * The Javascript code will be stored on the session, and can be retrieved by calling
     * [[getJsFlashes()]] or [[\craft\web\View::getBodyHtml()]].
     *
     * @param string      $js       the JS code block to be registered
     * @param integer     $position the position at which the JS script tag should be inserted
     *                              in a page.
     * @param string|null $key      the key that identifies the JS code block.
     *
     * @return void
     * @see getJsFlashes()
     * @see View::registerJs()
     */
    public function addJsFlash(string $js, int $position = View::POS_READY, string $key = null)
    {
        $scripts = $this->getJsFlashes();
        $scripts[] = [$js, $position, $key];
        $this->setFlash($this->jsFlashKey, $scripts);
    }

    /**
     * Returns the stored JS flashes.
     *
     * @param bool $delete Whether to delete the stored flashes. Defaults to `true`.
     *
     * @return array The stored JS flashes.
     * @see addJsFlash()
     */
    public function getJsFlashes(bool $delete = true): array
    {
        return $this->getFlash($this->jsFlashKey, [], $delete);
    }

    // Session-Based Authorization
    // -------------------------------------------------------------------------

    /**
     * Authorizes the user to perform an action for the duration of the session.
     *
     * @param string $action
     *
     * @return void
     */
    public function authorize(string $action)
    {
        $access = $this->get($this->authAccessParam, []);

        if (!in_array($action, $access, true)) {
            $access[] = $action;
            $this->set($this->authAccessParam, $access);
        }
    }

    /**
     * Deauthorizes the user from performing an action.
     *
     * @param string $action
     *
     * @return void
     */
    public function deauthorize(string $action)
    {
        $access = $this->get($this->authAccessParam, []);
        $index = array_search($action, $access, true);

        if ($index !== false) {
            array_splice($access, $index, 1);
            $this->set($this->authAccessParam, $access);
        }
    }

    /**
     * Returns whether the user is authorized to perform an action.
     *
     * @param string $action
     *
     * @return bool
     */
    public function checkAuthorization(string $action): bool
    {
        $access = $this->get($this->authAccessParam, []);

        return in_array($action, $access, true);
    }
}
