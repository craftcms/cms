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
     * @throws AuthFailedException
     * @return bool
     */
    public function handleAuthRequest(): bool;

    /**
     * Initiate an login request
     *
     * @throws AuthFailedException
     * @return bool
     */
    public function handleLoginRequest(): bool;

    /**
     * Initiate a logout request
     *
     * @throws AuthFailedException
     * @return bool
     */
    public function handleLogoutRequest(): bool;

    /**
     * Handle an auth response
     *
     * @throws AuthFailedException
     * @return bool
     */
    public function handleAuthResponse(): bool;

    /**
     * Handle a login response
     *
     * @throws AuthFailedException
     * @return bool
     */
    public function handleLoginResponse(): bool;

    /**
     * Handle a logout response
     *
     * @throws AuthFailedException
     * @return bool
     */
    public function handleLogoutResponse(): bool;

    /**
     * @return string|null The site login HTML
     */
    public function getSiteLoginHtml(): string | null;

    /**
     * @return string|null The admin login HTML
     */
    public function getCpLoginHtml(): string | null;

    /**
     * @return string|null The site logout HTML
     */
    public function getSiteLogoutHtml(): string | null;

    /**
     * @return string|null The admin logout HTML
     */
    public function getCpLogoutHtml(): string | null;

    /**
     * @return string|null The login request URL
     */
    public function getLoginRequestUrl(): string | null;

    /**
     * @return string|null The logout request URL
     */
    public function getLogoutRequestUrl(): string | null;

    /**
     * @return string|null The auth request URL
     */
    public function getAuthRequestUrl(): string | null;

    /**
     * @return string|null The response URL
     */
    public function getResponseUrl(): string | null;

//    /**
//     * @return string|null The login response URL
//     */
//    public function getLoginResponseUrl(): string | null;
//
//    /**
//     * @return string|null The logout response URL
//     */
//    public function getLogoutResponseUrl(): string | null;
//
//    /**
//     * @return string|null The auth response URL
//     */
//    public function getAuthResponseUrl(): string | null;
}