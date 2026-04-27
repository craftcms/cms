<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\auth\sso;

use craft\base\ModelInterface;
use craft\errors\SsoFailedException;
use CraftCms\Cms\Component\Contracts\ComponentInterface;
use yii\web\Request;
use yii\web\Response;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @internal
 * @since 5.3.0
 * @deprecated 6.0.0 use the Laravel Socialite {@see \CraftCms\Cms\Auth\OAuth\OAuth} implementation instead.
 */
interface ProviderInterface extends ComponentInterface, ModelInterface
{
    /**
     * Get the unique handle for the provider
     *
     * @return string
     */
    public function getHandle(): string;

    /**
     * Handle a request to authenticate against an identity provider
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     *
     * @throws SsoFailedException
     */
    public function handleRequest(Request $request, Response $response): Response;

    /**
     * Handle the response from an identity provider
     *
     * @param Request $request
     * @param Response $response
     * @return bool
     *
     * @throws SsoFailedException
     */
    public function handleResponse(Request $request, Response $response): bool;

    /**
     * HTML that we present an unauthenticated user to log in to the site.  Typically, this would b
     * a button or form
     *
     * @return string|null The site login HTML
     */
    public function getSiteLoginHtml(): string | null;

    /**
     * HTML that we present an unauthenticated user to log in to the admin panel.  Typically, this would b
     * a button or form
     *
     * @return string|null The admin login HTML
     */
    public function getCpLoginHtml(): string | null;
}
