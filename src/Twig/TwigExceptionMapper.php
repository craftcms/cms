<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig;

use CraftCms\Cms\Support\Facades\Twig;
use Exception;
use Illuminate\Support\Collection;
use ReflectionProperty;
use Throwable;
use Twig\Error\RuntimeError;
use Twig\Template;

final readonly class TwigExceptionMapper
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
            })
            ->when(
                $viewIndex !== null && str_ends_with($exception->getFile(), '.twig'),
                fn (Collection $trace) => $trace->slice($viewIndex + 1)  // Remove all traces before the view
            )
            ->all();

        if ($exception instanceof Exception) {
            $traceProperty = new ReflectionProperty('Exception', 'trace');
            $traceProperty->setValue($exception, $trace);
        }

        return $exception;
    }

    /**
     * Attempts to resolve a compiled template file path and line number to its source template path and line number.
     *
     * @param  string  $path  The compiled template path
     * @param  int|null  $line  The line number from the compiled template
     * @return array|false The resolved template path and line number, or `false` if the path couldn’t be determined.
     *                     If a template path could be determined but not the template line number, the line number will be null.
     */
    public function resolveTemplatePathAndLine(string $path, ?int $line): array|false
    {
        if (! str_contains($path, 'compiled_templates')) {
            return false;
        }

        $contents = file_get_contents($path);

        if (! preg_match('/^class (\w+)/m', $contents, $match)) {
            return false;
        }

        $class = $match[1];
        if (! class_exists($class, false) || ! is_subclass_of($class, Template::class)) {
            return false;
        }

        $template = new $class(Twig::get());
        $src = $template->getSourceContext();
        $templatePath = $src->getPath() ?: null;
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
}
