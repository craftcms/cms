<?php

declare(strict_types=1);

namespace CraftCms\Cms\View;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Support\Facades\Path;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\View\FileViewFinder;

enum TemplateMode: string
{
    case Cp = 'cp';
    case Site = 'site';

    public static function get(): self
    {
        if ($mode = Context::getHidden(self::class)) {
            return $mode;
        }

        $mode = self::forRequest(request());

        Context::addHidden(self::class, $mode);

        return $mode;
    }

    public static function forRequest(Request $request): self
    {
        return $request->isCpRequest() ? self::Cp : self::Site;
    }

    public static function set(self $mode): void
    {
        Context::addHidden(self::class, $mode);

        /**
         * CP mode puts Craft's control panel views first, so view() finds them without a 'craftcms::' prefix.
         * Site mode takes them out again, so the host app's views win, such as its own `app` layout.
         */
        $cpPaths = [
            dirname(__DIR__, 2).'/resources/views',
            dirname(__DIR__, 2).'/resources/templates',
        ];

        /** @var FileViewFinder $finder */
        $finder = view()->getFinder();
        $otherPaths = array_values(array_diff($finder->getPaths(), $cpPaths));
        $paths = $mode === self::Cp ? [...$cpPaths, ...$otherPaths] : $otherPaths;

        if ($paths !== $finder->getPaths()) {
            $finder->setPaths($paths);
            // Forget views found under the old paths
            $finder->flush();
        }
    }

    public static function is(self $mode): bool
    {
        return self::get() === $mode;
    }

    /**
     * Switches to a template mode while the callback runs, then switches back.
     *
     * Use `set()` only for a switch that should last for the rest of the request.
     *
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    public static function with(self $mode, callable $callback): mixed
    {
        $original = self::get();

        try {
            self::set($mode);

            return $callback();
        } finally {
            self::set($original);
        }
    }

    /**
     * @return string[]
     */
    public function defaultTemplateExtensions(): array
    {
        return match ($this) {
            self::Cp => ['twig', 'html', 'blade.php'],
            self::Site => Cms::config()->defaultTemplateExtensions,
        };
    }

    /**
     * @return string[]
     */
    public function indexTemplateFilenames(): array
    {
        return match ($this) {
            self::Cp => ['index'],
            self::Site => Cms::config()->indexTemplateFilenames,
        };
    }

    public function privateTemplateTrigger(): string
    {
        return match ($this) {
            self::Cp => '_',
            self::Site => Cms::config()->privateTemplateTrigger,
        };
    }

    public function templatesPath(): string
    {
        return rtrim((string) match ($this) {
            self::Cp => Path::cpTemplates(),
            self::Site => Path::siteTemplates(),
        }, '/\\');
    }

    /** @return array<string, list<string>> */
    public function templateRoots(): array
    {
        return app(TemplateRoots::class)->roots($this);
    }
}
