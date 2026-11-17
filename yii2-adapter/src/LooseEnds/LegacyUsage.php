<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\LooseEnds;

use CraftCms\Cms\Cms;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Records when the project’s own code relies on an adapter compatibility path, for the Yii2 Loose Ends utility.
 *
 * Usage that comes from a plugin or other package is ignored, since the fix for it is a package update.
 *
 * ```php
 * LegacyUsage::record('craft-variable.app', 'Templates use `craft.app`.');
 * ```
 *
 * @internal
 */
#[Singleton]
class LegacyUsage
{
    /** @var array<string, array{key: string, message: string, section: Section, severity: Severity, file: string|null, line: int|null}> */
    private array $pending = [];

    public function __construct(
        private readonly CodeOwnership $ownership,
        private readonly LooseEndsStore $store,
    ) {
    }

    /**
     * Records that a compatibility path ran.
     *
     * @param string $key Identifies the compatibility path.
     * @param string $message What the project relies on, and what to use instead.
     * @param string|null $file The project file responsible, if it’s known. Otherwise it’s found from the call stack.
     * @param bool $requestLevel Whether the usage comes from the request itself, such as a legacy URL format, rather
     * than from code.
     */
    public static function record(
        string $key,
        string $message,
        ?string $file = null,
        ?int $line = null,
        Section $section = Section::Code,
        Severity $severity = Severity::Breaks,
        bool $requestLevel = false,
    ): void {
        app(self::class)->add($key, $message, $file, $line, $section, $severity, $requestLevel);
    }

    public function add(
        string $key,
        string $message,
        ?string $file = null,
        ?int $line = null,
        Section $section = Section::Code,
        Severity $severity = Severity::Breaks,
        bool $requestLevel = false,
    ): void {
        if (!$requestLevel) {
            if ($file === null) {
                $origin = $this->ownership->projectOrigin(debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS));

                if ($origin === null) {
                    return;
                }

                [$file, $line] = $origin;
            } elseif (!$this->ownership->isProjectFile($file)) {
                return;
            }

            $file = $this->ownership->relativePath($file);
        }

        $this->pending["$key|$file|$line"] ??= [
            'key' => $key,
            'message' => $message,
            'section' => $section,
            'severity' => $severity,
            'file' => $file,
            'line' => $line,
        ];
    }

    /**
     * Returns the usage recorded during this request that hasn’t been stored yet.
     *
     * @return list<array{key: string, message: string, section: Section, severity: Severity, file: string|null, line: int|null}>
     */
    public function pending(): array
    {
        return array_values($this->pending);
    }

    /**
     * Stores the usage recorded during this request.
     */
    public function flush(): void
    {
        if ($this->pending === []) {
            return;
        }

        $usages = array_values($this->pending);
        $this->pending = [];

        if (!Cms::isInstalled()) {
            return;
        }

        try {
            $this->store->recordUsages($usages);
        } catch (Throwable $e) {
            Log::warning("Couldn’t save Yii2 Loose Ends usage: {$e->getMessage()}", [__METHOD__]);
        }
    }
}
