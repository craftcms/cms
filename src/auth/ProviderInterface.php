<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\auth;

use craft\auth\mapper\UserMapInterface;
use craft\base\SavableComponentInterface;
use craft\errors\AuthFailedException;
use yii\web\Request;
use yii\web\Response;

/**
 * AuthHandler handles successful authentication via Yii auth component
 */
interface ProviderInterface extends SavableComponentInterface
{
    /**
     * Get the unique handle for the provider
     *
     * @return string
     */
    public function getHandle(): string;

    /**
     * Initiate an auth request
     *
     * @param Request $request
     * @param Response $response
     *
     * @throws AuthFailedException
     * @return Response
     */
    public function handleAuthRequest(Request $request, Response $response): Response;

    /**
     * Initiate an login request
     *
     * @param Request $request
     * @param Response $response
     *
     * @throws AuthFailedException
     * @return bool
     */
    public function handleLoginRequest(Request $request, Response $response): Response;

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
     * @return string|null The site login HTML
     */
    public function getSiteLoginHtml(): string | null;

    /**
     * @return string|null The admin login HTML
     */
    public function getCpLoginHtml(): string | null;

    /**
     * @return string|null The login request URL
     */
    public function getLoginRequestUrl(): string | null;

    /**
     * @return string|null The auth request URL
     */
    public function getAuthRequestUrl(): string | null;

    /**
     * @return string|null The response URL
     */
    public function getResponseUrl(): string | null;

    /**
     * @return string|null The login response URL
     */
    public function getLoginResponseUrl(): string | null;
//
//    /**
//     * @return string|null The auth response URL
//     */
//    public function getAuthResponseUrl(): string | null;
}