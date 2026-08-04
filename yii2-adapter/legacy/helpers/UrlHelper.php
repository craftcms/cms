<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use CraftCms\Cms\Support\Url;

/**
 * Class Url
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated 6.0.0 use {@see Url} instead.
 */
class UrlHelper extends Url
{
    /**
     * Returns a CP referral URL.
     *
     * @return string|null
     * @since 5.9.0
     * @deprecated in 5.10.0
     */
    public static function cpReferralUrl(): ?string
    {
        $request = request();
        $referrer = $request->header('referer');

        if ($referrer === null || $referrer === '') {
            return null;
        }

        // Make sure it didn't refer itself
        if ($referrer === $request->fullUrl()) {
            return null;
        }

        // Make sure the CP referred it
        if (!str_starts_with($referrer, self::baseCpUrl())) {
            return null;
        }

        return $referrer;
    }
}
