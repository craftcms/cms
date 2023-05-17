<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\auth\oidc;

use craft\auth\AbstractProvider;

abstract class AbstractOpenIdConnectProvider extends AbstractProvider
{
    /**
     * @var string
     */
    public string $clientId;

    /**
     * @var string
     */
    public string $clientSecret;

    /**
     * @var string
     */
    public string $state;


}
