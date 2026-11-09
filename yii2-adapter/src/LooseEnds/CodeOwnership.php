<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\LooseEnds;

use Composer\InstalledVersions;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Support\Str;
use Illuminate\Container\Attributes\Singleton;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use Throwable;
use Twig\Template as TwigTemplate;

/**
 * Decides whether code belongs to the project, as opposed to Craft, the adapter, a framework, or an installed package.
 *
 * The Yii2 Loose Ends utility only reports project code. Anything that belongs to a plugin or other Composer package is left out,
 * because the fix is a package update rather than work in the project.
 *
 * @internal
 */
#[Singleton]
class CodeOwnership
{
    /**
     * Composer packages whose frames are skipped when looking for the code that triggered a compatibility path,
     * because they only pass calls through.
     */
    private const array PASS_THROUGH_PACKAGES = [
        'craftcms/cms',
        'craftcms/yii2-adapter',
        'laravel/framework',
        'twig/twig',
        'yiisoft/',
        'symfony/',
        'composer/',
    ];

    /** @var array<string, string>|null Package install paths, keyed by path, with package names as values. */
    private ?array $packagePaths = null;

    private ?string $basePath = null;

    /** @var list<string>|null */
    private ?array $passThroughPaths = null;

    /**
     * Finds the project file and line that triggered a compatibility path.
     *
     * Returns `null` when the call came from a plugin or other package, or from nowhere in the project.
     *
     * @param list<array<string, mixed>> $frames `debug_backtrace()` frames, with objects
     * @return array{0: string, 1: int|null}|null
     */
    public function projectOrigin(array $frames): ?array
    {
        foreach ($frames as $i => $frame) {
            $file = $frame['file'] ?? null;

            if (!is_string($file) || $file === '') {
                continue;
            }

            $line = isset($frame['line']) ? (int)$frame['line'] : null;
            $template = $this->templateForFrame($frames, $i, $file);

            if ($template !== null) {
                $path = $template->getSourceContext()->getPath();

                if ($path === '') {
                    continue;
                }

                $file = $path;
                $line = $this->templateLine($template, $line);
            }

            if ($this->isPassThrough($file)) {
                continue;
            }

            return $this->isProjectFile($file) ? [$file, $line] : null;
        }

        return null;
    }

    /**
     * Returns whether a file belongs to the project rather than to Craft, the adapter, or an installed package.
     */
    public function isProjectFile(string $file): bool
    {
        $file = $this->normalize($file);
        $basePath = $this->basePath();

        if (!str_starts_with($file, $basePath . '/')) {
            return false;
        }

        if ($this->isPassThrough($file)) {
            return false;
        }

        return $this->packageForFile($file) === null;
    }

    /**
     * Returns the name of the installed package a file belongs to, other than the root package.
     */
    public function packageForFile(string $file): ?string
    {
        $file = $this->normalize($file);

        foreach ($this->packagePaths() as $path => $name) {
            if (str_starts_with($file, $path . '/')) {
                return $name;
            }
        }

        return null;
    }

    /**
     * Returns where a callable is defined.
     *
     * @return array{0: string, 1: int|null}|null
     */
    public function callableLocation(mixed $callable): ?array
    {
        try {
            if (is_string($callable) && str_contains($callable, '::')) {
                $callable = explode('::', $callable, 2);
            }

            $reflection = match (true) {
                is_array($callable) && count($callable) === 2 => new ReflectionMethod($callable[0], $callable[1]),
                is_string($callable) || $callable instanceof \Closure => new ReflectionFunction($callable),
                is_object($callable) && method_exists($callable, '__invoke') => new ReflectionMethod($callable, '__invoke'),
                default => null,
            };
        } catch (Throwable) {
            return null;
        }

        $file = $reflection?->getFileName();

        return is_string($file) ? [$file, $reflection->getStartLine() ?: null] : null;
    }

    /**
     * Returns a file path relative to the project root, for display.
     */
    public function relativePath(string $file): string
    {
        $file = $this->normalize($file);
        $basePath = $this->basePath();

        return str_starts_with($file, $basePath . '/') ? substr($file, strlen($basePath) + 1) : $file;
    }

    /**
     * Returns the Twig template whose compiled code is running at a frame’s call site.
     *
     * @param list<array<string, mixed>> $frames
     */
    private function templateForFrame(array $frames, int $i, string $file): ?TwigTemplate
    {
        $object = $frames[$i + 1]['object'] ?? null;

        if (!$object instanceof TwigTemplate) {
            return null;
        }

        return new ReflectionClass($object)->getFileName() === $file ? $object : null;
    }

    private function templateLine(TwigTemplate $template, ?int $compiledLine): ?int
    {
        if ($compiledLine === null) {
            return null;
        }

        // Debug info maps compiled lines to template lines, from the bottom of the compiled class up.
        foreach ($template->getDebugInfo() as $codeLine => $templateLine) {
            if ($codeLine <= $compiledLine) {
                return $templateLine;
            }
        }

        return null;
    }

    private function isPassThrough(string $file): bool
    {
        $file = $this->normalize($file);

        foreach ($this->passThroughPaths() as $path) {
            if (str_starts_with($file, $path . '/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function passThroughPaths(): array
    {
        if ($this->passThroughPaths !== null) {
            return $this->passThroughPaths;
        }

        $adapterPath = dirname(__DIR__, 2);
        $paths = [$this->normalize(dirname(new ReflectionClass(Cms::class)->getFileName(), 2) . '/src')];

        foreach (['src', 'legacy', 'lib', 'bootstrap', 'routes', 'resources'] as $dir) {
            $paths[] = $this->normalize("$adapterPath/$dir");
        }

        foreach ($this->packagePaths() as $path => $name) {
            foreach (self::PASS_THROUGH_PACKAGES as $prefix) {
                if ($name === $prefix || (str_ends_with($prefix, '/') && str_starts_with($name, $prefix))) {
                    $paths[] = $path;
                }
            }
        }

        return $this->passThroughPaths = array_values(array_unique($paths));
    }

    /**
     * @return array<string, string>
     */
    private function packagePaths(): array
    {
        if ($this->packagePaths !== null) {
            return $this->packagePaths;
        }

        $root = InstalledVersions::getRootPackage()['name'];
        $basePath = $this->basePath();
        $paths = [];

        foreach (InstalledVersions::getInstalledPackages() as $name) {
            if ($name === $root) {
                continue;
            }

            $path = InstalledVersions::getInstallPath($name);

            if ($path === null || !is_dir($path)) {
                continue;
            }

            $path = $this->normalize($path);

            // A package that contains the project root, such as a test harness, doesn’t own the project’s files.
            if ($path === $basePath || str_starts_with($basePath, $path . '/')) {
                continue;
            }

            $paths[$path] = $name;
        }

        // Check deeper paths first, so a package nested inside another package’s directory wins.
        uksort($paths, fn(string $a, string $b) => strlen($b) <=> strlen($a));

        return $this->packagePaths = $paths;
    }

    private function basePath(): string
    {
        return $this->basePath ??= $this->normalize(base_path());
    }

    private function normalize(string $path): string
    {
        $real = realpath($path);

        return Str::replace('\\', '/', rtrim($real !== false ? $real : $path, '/\\'));
    }
}
