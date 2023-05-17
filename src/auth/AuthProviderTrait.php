<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\auth;

use craft\helpers\UrlHelper;

trait AuthProviderTrait
{
    /**
     * @var string|null Name
     */
    public ?string $name = null;

    /**
     * @var string|null Handle
     */
    public ?string $handle = null;

    /**
     * @var string|null UID
     */
    public ?string $uid = null;

    /**
     * @return string|null
     */
    public function getSiteLoginHtml(): string|null
    {
        return null;
    }

    /**
     * @return string|null
     */
    public function getCpLoginHtml(): string|null
    {
        return null;
    }

    /**
     * @return string|null
     */
    public function getSiteLogoutHtml(): string|null
    {
        return null;
    }

    /**
     * @return string|null
     */
    public function getCpLogoutHtml(): string|null
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function getLoginRequestUrl(): string | null
    {
        return UrlHelper::actionUrl('auth/request-login', ['provider' => $this->handle], null, false);
    }

    /**
     * @inheritDoc
     */
    public function getLogoutRequestUrl(): string | null
    {
        return UrlHelper::actionUrl('auth/request-logout', ['provider' => $this->handle], null, false);
    }

    /**
     * @inheritDoc
     */
    public function getAuthRequestUrl(): string | null
    {
        return UrlHelper::actionUrl('auth/request-session', ['provider' => $this->handle], null, false);
    }

    /**
     * @inheritDoc
     */
    public function getLoginResponseUrl(): string | null
    {
        return UrlHelper::actionUrl('auth/response', ['provider' => $this->handle], null, false);
    }

    /**
     * @inheritDoc
     */
    public function getLogoutResponseUrl(): string | null
    {
        return UrlHelper::actionUrl('auth/response', ['provider' => $this->handle], null, false);
    }

    /**
     * @inheritDoc
     */
    public function getAuthResponseUrl(): string | null
    {
        return UrlHelper::actionUrl('auth/response', ['provider' => $this->handle], null, false);
    }
}
