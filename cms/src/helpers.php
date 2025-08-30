<?php

namespace CraftCms\Cms;

use Closure;
use craft\helpers\Number;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Support\PHP;
use CraftCms\Cms\Support\Str;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

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

/**
 * Sets PHP’s memory limit to the maximum specified by the
 * <config5:phpMaxMemoryLimit> config setting, and gives the script an
 * unlimited amount of time to execute.
 */
function maxPowerCaptain(): void
{
    // Don't mess with the memory_limit, even at the config's request, if it's already set to -1 or >= 1.5GB
    $memoryLimit = PHP::configValueInBytes('memory_limit');

    if ($memoryLimit !== -1 && $memoryLimit < 1024 * 1024 * 1536) {
        $maxMemoryLimit = app(GeneralConfig::class)->phpMaxMemoryLimit;
        @ini_set('memory_limit', $maxMemoryLimit ?: '1536M');
    }

    // Try to reset time limit
    if (! function_exists('set_time_limit') || ! @set_time_limit(0)) {
        Log::warning('set_time_limit() is not available', [__METHOD__]);
    }
}

/**
 * Calls the given closure with all error reporting silenced, and returns its response.
 *
 * @param  int|null  $mask  Error levels to suppress, default value NULL indicates all warnings and below.
 */
function silence(Closure|string $callable, ?int $mask = null): mixed
{
    // loosely based on Composer\Util\Silencer
    if (! isset($mask)) {
        $mask = E_WARNING | E_NOTICE | E_USER_WARNING | E_USER_NOTICE | E_DEPRECATED | E_USER_DEPRECATED;
    }

    $old = error_reporting();
    error_reporting($old & ~$mask);

    try {
        return $callable();
    } finally {
        error_reporting($old);
    }
}
