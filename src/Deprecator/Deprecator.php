<?php

declare(strict_types=1);

namespace CraftCms\Cms\Deprecator;

use CraftCms\Cms\Deprecator\Exceptions\DeprecationException;
use CraftCms\Cms\Deprecator\Models\DeprecationError;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Support\Template;
use CraftCms\Cms\Twig\TwigExceptionMapper;
use CraftCms\Cms\View\TemplateResolver;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Log;
use Throwable;
use Twig\Extension\CoreExtension;
use Twig\Template as TwigTemplate;

/**
 * @since 6.0.0
 */
#[Scoped]
class Deprecator
{
    /**
     * @var bool Whether deprecation warnings should throw exceptions rather than being logged.
     */
    public static bool $throwExceptions = false;

    /**
     * @var 'db'|'logs'|false Whether deprecation warnings should be logged in the database ('db'), error logs ('logs'), or not at all (false).
     *
     * Changing this will prevent deprecation warnings from showing up in the "Deprecation Warnings" utility
     * or in the "Deprecated" panel in the Debug Toolbar.
     */
    public static string|false $logTarget = 'db';

    /**
     * @var DeprecationError[] The deprecation warnings that were logged in the current request
     */
    private array $requestLogs = [];

    /**
     * @var DeprecationError[] The deprecation warnings that still need to be stored in the DB
     */
    private array $pendingRequestLogs = [];

    /**
     * @var DeprecationError[]|null All the unique deprecation warnings that have been logged
     */
    private ?array $allLogs = null;

    public function log(string $key, string $message, ?string $file = null, ?int $line = null): void
    {
        if (self::$logTarget === false) {
            return;
        }

        if (self::$logTarget === 'logs') {
            Log::warning($message, ['deprecation-error']);
        }

        // Get the debug backtrace
        $traces = debug_backtrace();

        if ($file === null) {
            [$file, $line] = $this->findOrigin($traces);
        }

        if (self::$throwExceptions) {
            throw new DeprecationException($message, $file, $line);
        }

        $fingerprint = $file.($line ? ':'.$line : '');

        // Don't log the same key/fingerprint twice in the same request
        $this->requestLogs["$key-$fingerprint"] = $this->pendingRequestLogs["$key-$fingerprint"] = new DeprecationError([
            'key' => $key,
            'fingerprint' => $fingerprint,
            'lastOccurrence' => now(),
            'file' => $file,
            'line' => $line,
            'message' => $message,
            'traces' => $this->processStackTrace($traces),
        ]);
    }

    public function storeLogs(): void
    {
        if (! $this->storeLogsInDb()) {
            return;
        }

        foreach ($this->pendingRequestLogs as $log) {
            try {
                DeprecationError::updateOrCreate([
                    'key' => $log->key,
                    'fingerprint' => $log->fingerprint,
                ], [
                    'lastOccurrence' => $log->lastOccurrence,
                    'file' => $log->file,
                    'line' => $log->line,
                    'message' => $log->message,
                    'traces' => $log->traces,
                ]);
            } catch (Throwable $e) {
                Log::warning("Couldn't save deprecation warning: {$e->getMessage()}", [__METHOD__]);
                // Craft probably isn’t installed yet
                break;
            }
        }

        $this->pendingRequestLogs = [];
    }

    /** @return list<DeprecationError> */
    public function getRequestLogs(): array
    {
        return $this->requestLogs;
    }

    public function getTotalLogs(): int
    {
        return DeprecationError::count();
    }

    /** @return list<DeprecationError> */
    public function getLogs(?int $limit = null): array
    {
        return $this->allLogs ?? $this->allLogs = DeprecationError::query()
            ->limit($limit)
            ->latest('lastOccurrence')
            ->get()
            ->all();
    }

    public function getLogById(int $logId): ?DeprecationError
    {
        return DeprecationError::find($logId);
    }

    public function deleteLogById(int $id): bool
    {
        return (bool) DeprecationError::destroy($id);
    }

    public function deleteAllLogs(): bool
    {
        return (bool) DeprecationError::query()->delete();
    }

    private function storeLogsInDb(): bool
    {
        return ! self::$throwExceptions && self::$logTarget === 'db';
    }

    /**
     * Returns the file and line number that should be associated with the error.
     *
     * That's where the deprecated code was called from: a template line, or else a PHP file and line.
     * Wrappers that forward to [[log()]] can pass their own backtrace and forward the result as `$file` and `$line`.
     *
     * @param  list<array<string, mixed>>  $traces  debug_backtrace() results whose first frame is the logging call
     * @return array{0: string, 1: ?int} [file, line]
     */
    public function findOrigin(array $traces): array
    {
        // Frame 0 is the logging call; skip the facade that forwarded it, if any
        $t = 1;
        while (
            isset($traces[$t]['class'], $traces[$t]['function']) &&
            $traces[$t]['function'] === '__callStatic' &&
            is_a($traces[$t]['class'], Facade::class, true)
        ) {
            $t++;
        }

        // $traces[$t] is now the deprecated code. Skip the frames that dispatched the call to it on behalf of the actual caller.
        while (isset($traces[$t + 1]) && $this->isDispatchFrame($traces[$t + 1])) {
            $t++;
        }

        $file = $traces[$t]['file'] ?? '';
        $line = $traces[$t]['line'] ?? null;
        $template = $traces[$t + 1]['object'] ?? null;

        if ($template instanceof TwigTemplate) {
            $templateName = $template->getTemplateName();
            $file = app(TemplateResolver::class)->resolve($templateName) ?: $templateName;
            $line = $this->findTemplateLine($template, $line);
        }

        return [$file, $line];
    }

    /**
     * Returns whether the given trace is a magic method or Twig attribute lookup that called the next frame on someone else's behalf.
     *
     * @param  array<string, mixed>  $trace
     */
    private function isDispatchFrame(array $trace): bool
    {
        $class = $trace['class'] ?? null;
        $function = $trace['function'] ?? null;

        return
            in_array($function, ['__get', '__call', '__callStatic'], true) ||
            ($class === CoreExtension::class && $function === 'getAttribute') ||
            ($class === Template::class && $function === 'attribute');
    }

    /**
     * Returns a simplified version of the stack trace.
     *
     * @param  list<array<string, mixed>>  $traces  debug_backtrace() results leading up to [[log()]]
     * @return list<array{objectClass: class-string|null, file: string|null, line: int|null, class: class-string|null, method: string|null, args: string|null}>
     */
    private function processStackTrace(array $traces): array
    {
        $logTraces = [];

        foreach ($traces as $trace) {
            $file = $trace['file'] ?? null;
            $line = $trace['line'] ?? null;
            try {
                $templateInfo = app(TwigExceptionMapper::class)->resolveTemplatePathAndLine($file ?? '', $line);
            } catch (Throwable) {
                $templateInfo = false;
            }

            if ($templateInfo !== false) {
                [$file, $line] = $templateInfo;
            }

            $logTraces[] = [
                'objectClass' => ! empty($trace['object']) ? $trace['object']::class : null,
                'file' => $file,
                'line' => $line,
                'class' => ! empty($trace['class']) ? $trace['class'] : null,
                'method' => ! empty($trace['function']) ? $trace['function'] : null,
                'args' => ! empty($trace['args']) ? $this->argsToString($trace['args']) : null,
            ];
        }

        return $logTraces;
    }

    /**
     * Returns the Twig template that should be associated with the deprecation error, if any.
     */
    private function findTemplateLine(TwigTemplate $template, ?int $actualCodeLine = null): ?int
    {
        if ($actualCodeLine === null) {
            return null;
        }

        // getDebugInfo() maps compiled code lines to template lines, going upward, so the first code line that's <= the trace line will be the match
        return array_find($template->getDebugInfo(), fn ($templateLine, $codeLine) => $codeLine <= $actualCodeLine);
    }

    /**
     * Converts an array of method arguments to a string.
     *
     * @param  array<array-key, mixed>  $args
     */
    private function argsToString(array $args): string
    {
        $isAssoc = Arr::isAssoc($args);
        $limit = 5;

        return Collection::make($args)
            ->take($limit)
            ->map(function ($value, $key) use ($isAssoc) {
                $value = match (true) {
                    is_object($value) => $value::class,
                    is_bool($value) => $value ? 'true' : 'false',
                    is_string($value) => mb_convert_encoding(Str::limit($value, 64), 'UTF-8'),
                    is_array($value) => '['.$this->argsToString($value).']',
                    is_null($value) => 'null',
                    is_resource($value) => 'resource',
                    default => $value,
                };

                return match (true) {
                    is_string($key) => '"'.$key.'" => '.$value,
                    $isAssoc => $key.' => '.$value,
                    default => $value,
                };
            })
            ->when(count($args) > $limit, fn (Collection $collection) => $collection->add('...'))
            ->implode(', ');
    }
}
