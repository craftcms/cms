<?php

declare(strict_types=1);

namespace CraftCms\Cms;

use Closure;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\PHP;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Support\Typecast;
use CraftCms\Cms\Twig\TemplateRenderer;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Stringable;

function t(string|Stringable|null $id, array $parameters = [], ?string $category = 'app', ?string $locale = null): string
{
    return I18N::translate($id ?? '', $parameters, $category, $locale);
}

function action_url(string $url): string
{
    return Str::start($url, Str::finish(Cms::config()->actionTrigger, '/'));
}

function cp_url(string $url): string
{
    return Str::start($url, Str::finish(Cms::config()->cpTrigger, '/'));
}

function cp_redirect(string $url, int $status = 302, array $headers = [], ?bool $secure = null): RedirectResponse
{
    return redirect(
        to: cp_url($url),
        status: $status,
        headers: $headers,
        secure: $secure
    );
}

function debugbar()
{
    return app()->bound('debugbar') ? app('debugbar') : optional();
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

function template(string $template, array $variables = [], ?TemplateMode $templateMode = null): string
{
    return app(TemplateRenderer::class)->renderTemplate($template, $variables, $templateMode);
}

function sandboxedTemplate(string $template, array $variables = [], ?TemplateMode $templateMode = null): string
{
    return app(TemplateRenderer::class)->renderSandboxedTemplate($template, $variables, $templateMode);
}

function pageTemplate(string $template, array $variables = [], ?TemplateMode $templateMode = null): string
{
    return app(TemplateRenderer::class)->renderPageTemplate($template, $variables, $templateMode);
}

function renderString(string $template, array $variables = [], TemplateMode $templateMode = TemplateMode::Site, bool $escapeHtml = false): string
{
    return app(TemplateRenderer::class)->renderString($template, $variables, $templateMode, $escapeHtml);
}

function renderSandboxedString(string $template, array $variables = [], TemplateMode $templateMode = TemplateMode::Site, bool $escapeHtml = false): string
{
    return app(TemplateRenderer::class)->renderSandboxedString($template, $variables, $templateMode, $escapeHtml);
}

function renderObjectTemplate(string $template, mixed $object, array $variables = [], TemplateMode $templateMode = TemplateMode::Site): string
{
    return app(TemplateRenderer::class)->renderObjectTemplate($template, $object, $variables, $templateMode);
}

function renderSandboxedObjectTemplate(string $template, mixed $object, array $variables = [], TemplateMode $templateMode = TemplateMode::Site): string
{
    return app(TemplateRenderer::class)->renderSandboxedObjectTemplate($template, $object, $variables, $templateMode);
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
