<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Craft;

/**
 * Class Csrf
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.1
 */
class Csrf
{
    /**
     * Get the CSRF token name.
     *
     * @return string
     */
    public static function tokenName(): string
    {
        return Craft::$app->getConfig()->getGeneral()->csrfTokenName;
    }

    /**
     * Get the CSRF token value.
     *
     * @return string
     */
    public static function tokenValue(): string
    {
        return Craft::$app->getRequest()->getCsrfToken();
    }
}
