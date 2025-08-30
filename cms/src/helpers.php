<?php

namespace CraftCms\Cms;

use craft\helpers\Number;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Support\Str;
use Illuminate\Http\RedirectResponse;

/** @since 6.0.0 */
function cp_url(string $url): string
{
    return Str::start($url, Str::finish(app(GeneralConfig::class)->cpTrigger, '/'));
}

/** @since 6.0.0 */
function cp_redirect(string $url, int $status = 302, array $headers = [], ?bool $secure = null): RedirectResponse
{
    return redirect(
        to: cp_url($url),
        status: $status,
        headers: $headers,
        secure: $secure
    );
}

/**
 * Normalizes an environment variable/constant name/CLI command option.
 *
 * It converts the following:
 *
 * - `'true'` → `true`
 * - `'false'` → `false`
 * - Numeric string → integer or float
 */
function normalizeValue(mixed $value): mixed
{
    if (! is_string($value)) {
        return $value;
    }

    switch (strtolower($value)) {
        case 'true':
            return true;
        case 'false':
            return false;
        case 'null':
            return null;
    }

    if (Number::isIntOrFloat($value)) {
        $intOrFloat = Number::toIntOrFloat($value);
        // make sure we didn't lose any precision
        if ((string) $intOrFloat === $value) {
            return $intOrFloat;
        }
    }

    return $value;
}

/**
 * Removes distribution info from a version string, and returns the highest version number found in the remainder.
 *
 * @since 6.0.0
 */
function normalizeVersion(string $version): string
{
    // Strip out the distribution info
    $versionPattern = '\d[\d.]*(-(dev|alpha|beta|rc)(\.?\d[\d.]*)?)?';

    if (! preg_match("/^((v|version\s*)?$versionPattern-?)+/i", $version, $match)) {
        return '';
    }
    $version = $match[0];

    // Return the highest version
    preg_match_all("/$versionPattern/i", $version, $matches, PREG_SET_ORDER);

    $versions = array_map(fn (array $match) => $match[0], $matches);

    usort($versions, fn ($a, $b) => match (true) {
        version_compare($a, $b, '<') => 1,
        version_compare($a, $b, '>') => -1,
        default => 0,
    });

    return reset($versions) ?: '';
}
