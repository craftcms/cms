<?php

declare(strict_types=1);

namespace CraftCms\Cms;

use BackedEnum;
use Closure;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Facades\Template;
use CraftCms\Cms\Support\PHP;
use CraftCms\Cms\Support\Typecast;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\User\Contracts\CraftUser;
use CraftCms\Cms\User\Elements\User as UserElement;
use CraftCms\Cms\View\TemplateEngine;
use CraftCms\Cms\View\TemplateMode;
use Fruitcake\LaravelDebugbar\LaravelDebugbar;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Optional;
use Stringable;
use UnitEnum;

/**
 * Returns the scalar value of the given enum, or the value itself if it's not an enum.
 */
function enum_value(mixed $value, mixed $default = null): mixed
{
    return match (true) {
        $value instanceof BackedEnum => $value->value,
        $value instanceof UnitEnum => $value->name,
        default => $value ?? value($default),
    };
}

function craftAsset(string $path, ?bool $secure = null): string
{
    $suffix = pathinfo($path, PATHINFO_EXTENSION) !== '' ? '?v='.Cms::version() : '';

    return asset("vendor/craft/$path", $secure).$suffix;
}

/** @param array<string, mixed> $parameters */
function t(string|Stringable|null $id, array $parameters = [], ?string $category = 'app', ?string $locale = null): string
{
    return I18N::translate($id ?? '', $parameters, $category, $locale);
}

/** @param array<string, mixed>|string|null $params */
function action_url(string $path = '', array|string|null $params = null, ?string $scheme = null): string
{
    return Url::actionUrl($path, $params, $scheme);
}

/** @param array<string, mixed>|string|null $params */
function cp_url(string $path = '', array|string|null $params = null, ?string $scheme = null): string
{
    return Url::cpUrl($path, $params, $scheme);
}

/** @param array<string, mixed>|string|null $params */
function site_url(string $path = '', array|string|null $params = null, ?string $scheme = null, ?int $siteId = null): string
{
    return Url::siteUrl($path, $params, $scheme, $siteId);
}

/** @param array<string, string> $headers */
function cp_redirect(string $url, int $status = 302, array $headers = [], ?bool $secure = null): RedirectResponse
{
    return redirect(
        to: cp_url($url),
        status: $status,
        headers: $headers,
        secure: $secure
    );
}

function debugbar(): LaravelDebugbar|Optional
{
    return app()->bound('debugbar') ? app('debugbar') : optional();
}

function currentUser(): ?CraftUser
{
    if (! Cms::isInstalled()) {
        return null;
    }

    $user = Auth::user();

    if ($user === null || $user instanceof CraftUser) {
        return $user;
    }

    throw new AuthenticationException(sprintf(
        'The authenticated user must implement %s to be used by Craft.',
        CraftUser::class,
    ));
}

function currentUserElement(): ?UserElement
{
    return currentUser()?->asElement();
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

    if (Typecast::isIntOrFloat($value)) {
        $intOrFloat = Typecast::toIntOrFloat($value);
        // make sure we didn't lose any precision
        if ((string) $intOrFloat === $value) {
            return $intOrFloat;
        }
    }

    return $value;
}

/**
 * Removes distribution info from a version string, and returns the highest version number found in the remainder.
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
        $maxMemoryLimit = Cms::config()->phpMaxMemoryLimit;
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
    $mask ??= E_WARNING | E_NOTICE | E_USER_WARNING | E_USER_NOTICE | E_DEPRECATED | E_USER_DEPRECATED;

    $old = error_reporting();
    error_reporting($old & ~$mask);

    try {
        return $callable();
    } finally {
        error_reporting($old);
    }
}

/** @param array<array-key, mixed> $variables */
function template(
    string $template,
    array $variables = [],
    ?TemplateMode $templateMode = null,
    TemplateEngine|string|null $renderer = null,
): string {
    return Template::renderTemplate($template, $variables, $templateMode, renderer: $renderer);
}

/** @param array<array-key, mixed> $variables */
function sandboxedTemplate(string $template, array $variables = [], ?TemplateMode $templateMode = null): string
{
    return Template::renderSandboxedTemplate($template, $variables, $templateMode);
}

/** @param array<array-key, mixed> $variables */
function pageTemplate(
    string $template,
    array $variables = [],
    ?TemplateMode $templateMode = null,
    TemplateEngine|string|null $renderer = null,
): string {
    return Template::renderPageTemplate($template, $variables, $templateMode, renderer: $renderer);
}

/** @param array<array-key, mixed> $variables */
function renderString(string $template, array $variables = [], TemplateMode $templateMode = TemplateMode::Site, bool $escapeHtml = false): string
{
    return Template::renderTwigString($template, $variables, $templateMode, $escapeHtml);
}

/** @param array<array-key, mixed> $variables */
function renderSandboxedString(string $template, array $variables = [], TemplateMode $templateMode = TemplateMode::Site, bool $escapeHtml = false): string
{
    return Template::renderSandboxedString($template, $variables, $templateMode, $escapeHtml);
}

/** @param array<array-key, mixed> $variables */
function renderObjectTemplate(string $template, mixed $object, array $variables = [], TemplateMode $templateMode = TemplateMode::Site, string|false $escaperStrategy = false): string
{
    return Template::renderObjectTemplate($template, $object, $variables, $templateMode, $escaperStrategy);
}

/** @param array<array-key, mixed> $variables */
function renderSandboxedObjectTemplate(string $template, mixed $object, array $variables = [], TemplateMode $templateMode = TemplateMode::Site): string
{
    return Template::renderSandboxedObjectTemplate($template, $object, $variables, $templateMode);
}

/**
 * Returns the backtrace as a string (omitting the final frame where this method was called).
 *
 * @param  int  $limit  The max number of stack frames to be included (0 means no limit)
 */
function backTraceAsString(int $limit = 0): string
{
    $frames = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $limit ? $limit + 1 : 0);

    array_shift($frames);

    $trace = '';

    foreach ($frames as $i => $frame) {
        $trace .= ($i !== 0 ? "\n" : '').
            '#'.$i.' '.
            (isset($frame['file']) ? sprintf('%s%s: ', $frame['file'], isset($frame['line']) ? "({$frame['line']})" : '') : '').
            ($frame['class'] ?? '').
            ($frame['type'] ?? '').
            /** @phpstan-ignore-next-line */
            (isset($frame['function']) ? "{$frame['function']}()" : '');
    }

    return $trace;
}

/**
 * Creates a UI component by its registered name, applying the given config
 * via its fluent setters.
 *
 *     ui('callout', ['variant' => 'warning'])->content(t('Careful!'));
 *
 * @param  array<string, mixed>  $config
 */
function ui(string $name, array $config = []): Cp\Components\ViewComponent
{
    return app(Cp\Components\ComponentRegistry::class)->make($name, $config);
}
