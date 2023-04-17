<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\auth;

use craft\base\SavableComponentInterface;
use craft\elements\User;
//use modules\auth\records\Auth;
use craft\errors\AuthFailedException;
use Yii;
use yii\authclient\ClientInterface;
use yii\helpers\ArrayHelper;
use yii\web\Request;
use yii\web\Response;

/**
 * AuthHandler handles successful authentication via Yii auth component
 */
interface ProviderInterface extends SavableComponentInterface
{
    /**
     * Initiate an auth request
     *
     * @param bool $isLogin
     * @throws AuthFailedException
     * @return bool
     */
    public function handleRequest(bool $isLogin): bool;

    /**
     * Handle an auth response
     *
     * @param bool $isLogin
     * @throws AuthFailedException
     * @return bool
     */
    public function handleResponse(bool $isLogin): bool;

    /**
     * @return string|null The site login HTML
     */
    public function siteLoginHtml(): string | null;

    /**
     * @return string|null The admin login HTML
     */
    public function cpLoginHtml(): string | null;
}