<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\auth\provider;

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
}
