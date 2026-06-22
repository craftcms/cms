<?php

declare(strict_types=1);

namespace CraftCms\Cms\View\BladeDirectives;

use CraftCms\Cms\View\TemplateCaches;
use Illuminate\Support\Facades\Context;
use Illuminate\View\Compilers\BladeCompiler;

class CacheDirective
{
    public static function register(BladeCompiler $blade): void
    {
        $blade->directive('craftCache', fn (string $expression) => self::compileCache($expression));
        $blade->directive('craftCacheIf', fn (string $expression) => self::compileConditionalCache('startIf', $expression));
        $blade->directive('craftCacheUnless', fn (string $expression) => self::compileConditionalCache('startUnless', $expression));
        $blade->directive('endCraftCache', fn (string $expression = '') => '<?php echo '.self::class.'::end(); } ?>');
    }

    public static function compileCache(string $expression): string
    {
        $cacheBody = '$__craftCacheBody'.str_replace('.', '', uniqid('', true));
        $defaultKey = 'blade:'.md5($cacheBody);
        $arguments = trim($expression) === ''
            ? var_export($defaultKey, true)
            : var_export($defaultKey, true).", $expression";

        return "<?php if (($cacheBody = ".self::class."::start($arguments)) !== null) { echo $cacheBody; } else { ?>";
    }

    public static function compileConditionalCache(string $method, string $expression): string
    {
        $cacheBody = '$__craftCacheBody'.str_replace('.', '', uniqid('', true));
        $defaultKey = 'blade:'.md5($cacheBody);
        $arguments = var_export($defaultKey, true).", $expression";

        return "<?php if (($cacheBody = ".self::class."::$method($arguments)) !== null) { echo $cacheBody; } else { ?>";
    }

    public static function start(
        string $defaultKey,
        ?string $key = null,
        bool $global = false,
        ?string $duration = null,
        mixed $expiration = null,
        bool $withResources = true,
        bool $condition = true,
        bool $unless = false,
    ): ?string {
        $ignoreCache = request()->isPreview() || request()->getHadToken() || ! $condition || $unless;
        $key ??= $defaultKey;
        $cache = app(TemplateCaches::class);

        Context::pushHidden(self::class, [
            'key' => $key,
            'global' => $global,
            'duration' => $duration,
            'expiration' => $expiration,
            'ignore' => $ignoreCache,
            'withResources' => $withResources,
        ]);

        if (! $ignoreCache) {
            $cachedBody = $cache->getTemplateCache($key, $global, $withResources);

            if ($cachedBody !== null) {
                Context::popHidden(self::class);

                return $cachedBody;
            }

            $cache->startTemplateCache($withResources, $global);
        }

        ob_start();

        return null;
    }

    public static function startIf(
        string $defaultKey,
        bool $condition,
        ?string $key = null,
        bool $global = false,
        ?string $duration = null,
        mixed $expiration = null,
        bool $withResources = true,
    ): ?string {
        return self::start($defaultKey, $key, $global, $duration, $expiration, $withResources, condition: $condition);
    }

    public static function startUnless(
        string $defaultKey,
        bool $unless,
        ?string $key = null,
        bool $global = false,
        ?string $duration = null,
        mixed $expiration = null,
        bool $withResources = true,
    ): ?string {
        return self::start($defaultKey, $key, $global, $duration, $expiration, $withResources, unless: $unless);
    }

    public static function end(): string
    {
        $body = (string) ob_get_clean();
        $context = Context::popHidden(self::class);

        if (! $context['ignore']) {
            app(TemplateCaches::class)->endTemplateCache(
                $context['key'],
                $context['global'],
                $context['duration'],
                $context['expiration'],
                $body,
                $context['withResources'],
            );
        }

        return $body;
    }
}
