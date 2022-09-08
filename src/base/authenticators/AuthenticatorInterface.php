<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base\authenticators;

interface AuthenticatorInterface
{
    public function authenticate(): AuthenticationResult;

    public static function getLoginHtml(): string;

}