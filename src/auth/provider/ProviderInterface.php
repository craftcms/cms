<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\auth\provider;

use craft\errors\AuthFailedException;
use yii\web\Request;
use yii\web\Response;

interface ProviderInterface
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
     * @throws AuthFailedException
     */
    public function handleRequest(Request $request, Response $response): Response;

    /**
     * Handle the response from an identity provider
     *
     * @param Request $request
     * @param Response $response
     * @return bool
     *
     * @throws AuthFailedException
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
