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
     * @inheritDoc
     */
    public function getHandle(): string
    {
        return $this->handle;
    }

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
     * @inheritDoc
     */
    public function getRequestUrl(): string | null
    {
        return UrlHelper::actionUrl('auth/request', ['provider' => $this->handle], null, false);
    }

    /**
     * @inheritDoc
     */
    public function getResponseUrl(): string | null
    {
        return UrlHelper::actionUrl('auth/response', ['provider' => $this->handle], null, false);
    }
}
