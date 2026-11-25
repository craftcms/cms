<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig;

use CraftCms\Cms\Support\Facades\Twig;
use CraftCms\Cms\View\TemplateMode;
use Error;
use Exception;
use ReflectionClass;
use ReflectionProperty;
use Throwable;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Template;

/**
 * @since 6.0.0
 */
readonly class TwigExceptionMapper
{
    /**
     * Maps an exception and replaces all references to compiled Twig
     * templates in the stack trace with references to the original source.
     */
    public function map(Throwable $exception): Throwable
    {
        if ($exception instanceof RuntimeError) {
            /**
             * When we get a Twig runtime error, we need the previous exception to be shown.
             */
            $exception = $exception->getPrevious() ?? $exception;
        }

        $viewIndex = null;

        $trace = collect($exception->getTrace())
            ->map(function (array $frame, int $index) use (&$viewIndex) {
                $templateInfo = $this->resolveTemplatePathAndLine($frame['file'] ?? '', $frame['line'] ?? null);

                if ($templateInfo !== false) {
                    [$frame['file'], $frame['line']] = $templateInfo;

                    $viewIndex ??= $index;
                }

                return $frame;
            });

        if ($viewIndex !== null && str_ends_with($exception->getFile(), '.twig')) {
            $trace = $trace->slice($viewIndex + 1);
        }

        // `Exception` and `Error` each declare their own private properties.
        $baseClass = $exception instanceof Exception ? Exception::class : Error::class;
        new ReflectionProperty($baseClass, 'trace')->setValue($exception, $trace->all());

        $location = $this->resolveTemplatePathAndLine($exception->getFile(), $exception->getLine());

        if ($location !== false) {
            new ReflectionProperty($baseClass, 'file')->setValue($exception, $location[0]);

            if ($location[1] !== null) {
                new ReflectionProperty($baseClass, 'line')->setValue($exception, $location[1]);
            }
        }

        return $exception;
    }

    /**
     * Attempts to resolve a compiled template file path and line number to its source template path and line number.
     *
     * @param  string  $path  The compiled template path
     * @param  int|null  $line  The line number from the compiled template
     * @return array{string, int|null}|false The resolved template path and line number, or `false` if the path couldn’t be determined.
     *                                       If a template path could be determined but not the template line number, the line number will be null.
     */
    public function resolveTemplatePathAndLine(string $path, ?int $line): array|false
    {
        if (! str_contains($path, 'compiled_templates')) {
            return false;
        }

        if (! is_file($path)) {
            return false;
        }

        $contents = @file_get_contents($path);

        if ($contents === false) {
            return false;
        }

        if (! preg_match('/^class (\w+)/m', $contents, $match)) {
            return false;
        }

        $class = $match[1];
        if (! class_exists($class, false) || ! is_subclass_of($class, Template::class)) {
            return false;
        }

        // Only the compiled class's metadata is needed, and its constructor could load other templates, such as traits,
        // that the current template mode can't resolve
        /** @var Template $template */
        $template = new ReflectionClass($class)->newInstanceWithoutConstructor();
        $templatePath = $this->resolveSourcePath($template, $contents) ?? $template->getTemplateName();
        $templateLine = null;

        if ($line !== null) {
            foreach ($template->getDebugInfo() as $codeLine => $thisTemplateLine) {
                if ($codeLine <= $line) {
                    $templateLine = $thisTemplateLine;
                    break;
                }
            }
        }

        return [$templatePath, $templateLine];
    }

    /**
     * Returns the source path of a compiled template.
     *
     * Outside debug mode, compiled templates don't store their source path, and Twig asks the loader for it, which
     * resolves the template name for the current template mode. The template may have been rendered in another mode,
     * where the same name can resolve to a different template, so this prefers the mode whose environment compiles the
     * template name to the same class. That class also depends on settings that can change while rendering, such as the
     * escaping strategy, so when no mode matches it, the path is used if only one mode resolves the name.
     */
    private function resolveSourcePath(Template $template, string $compiledCode): ?string
    {
        if (! str_contains($compiledCode, 'Source::createWithLazyPath(')) {
            return $template->getSourceContext()->getPath() ?: null;
        }

        $class = $template::class;
        $name = $template->getTemplateName();
        $modes = array_unique([TemplateMode::get(), TemplateMode::Site, TemplateMode::Cp], SORT_REGULAR);
        $paths = [];

        foreach ($modes as $mode) {
            $result = TemplateMode::with($mode, function () use ($mode, $name, $class): ?array {
                $twig = Twig::get($mode);

                try {
                    $path = $twig->getLoader()->getSourceContext($name)->getPath();
                } catch (LoaderError) {
                    return null;
                }

                return $path === '' ? null : [$path, $twig->getTemplateClass($name) === $class];
            });

            if ($result === null) {
                continue;
            }

            [$path, $compiledHere] = $result;

            if ($compiledHere) {
                return $path;
            }

            $paths[] = $path;
        }

        // Both modes can resolve the name to the same file, such as when a plugin registers the same template root for both
        $paths = array_unique($paths);

        return count($paths) === 1 ? $paths[0] : null;
    }
}
