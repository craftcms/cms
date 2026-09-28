<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use CraftCms\Cms\Site\Exceptions\SiteNotFoundException;
use CraftCms\Cms\Support\Url;
use Illuminate\Support\Uri;

/**
 * Class Url
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 3.0.0
 * @deprecated 6.0.0 use {@see Url} instead.
 */
class UrlHelper extends Url
{
    /**
     * Returns whether a given string appears to be a "full" URL (absolute, root-relative or protocol-relative).
     *
     * @deprecated in 6.0.0
     */
    public static function isFullUrl(string $url): bool
    {
        return static::isAbsoluteUrl($url) || str_starts_with($url, '/');
    }

    /**
     * Removes query string params from a URL.
     *
     * @param string[] $params
     * @deprecated in 6.0.0. {@see Url::removeParam()} should be used instead.
     */
    public static function removeParams(string $url, array $params): string
    {
        return static::removeParam($url, $params);
    }

    /**
     * Encodes a URL’s query string param values, except for `/`, `{`, and `}` characters.
     *
     * @deprecated in 6.0.0
     */
    public static function encodeParams(string $url): string
    {
        return static::removeParam($url, []);
    }

    /**
     * Returns a root-relative URL based on the given URL.
     *
     * @deprecated in 6.0.0
     */
    public static function rootRelativeUrl(string $url): string
    {
        if (static::isAbsoluteUrl($url) || static::isProtocolRelativeUrl($url) || static::isRootRelativeUrl($url)) {
            $path = Uri::of($url)->path();

            return $path === '/' ? '/' : '/' . ltrim($path, '/');
        }

        return '/' . ltrim($url, '/');
    }

    /**
     * Returns either the current site’s base URL or the control panel’s base URL, depending on the type of request this is.
     *
     * @throws SiteNotFoundException if this is a site request and yet there's no current site for some reason
     * @deprecated in 6.0.0
     */
    public static function baseUrl(): string
    {
        if (request()->isCpRequest()) {
            return static::baseCpUrl();
        }

        return static::baseSiteUrl();
    }

    /**
     * Returns the host info for the control panel or the current site, depending on the request type.
     *
     * @throws SiteNotFoundException
     * @deprecated in 6.0.0
     */
    public static function host(): string
    {
        return static::hostInfo(static::baseUrl());
    }

    /**
     * Returns the control panel's host.
     *
     * @deprecated in 6.0.0
     */
    public static function cpHost(): string
    {
        return static::hostInfo(static::baseCpUrl());
    }

    /**
     * Returns a CP referral URL.
     *
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
        if (!str_starts_with($referrer, static::baseCpUrl())) {
            return null;
        }

        return $referrer;
    }
}
