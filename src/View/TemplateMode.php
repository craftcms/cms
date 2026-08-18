<?php

declare(strict_types=1);

namespace CraftCms\Cms\View;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Support\Facades\Path;
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

        $mode = request()->isCpRequest() ? self::Cp : self::Site;

        Context::addHidden(self::class, $mode);

        return $mode;
    }

    public static function set(self $mode): void
    {
        Context::addHidden(self::class, $mode);

        if ($mode === self::Cp) {
            /**
             * Prepend the Craft CMS Control panel views when
             * we're in CP Template mode. This makes view()
             * work without a 'craftcms::' prefix.
             */
            if (TemplateMode::is(TemplateMode::Cp)) {
                $templates = Path::resources('templates');
                $views = Path::resources('views');

                /** @var FileViewFinder $finder */
                $finder = view()->getFinder();

                if (! in_array($templates, $finder->getPaths())) {
                    $finder->prependLocation($templates);
                }

                if (! in_array($views, $finder->getPaths())) {
                    $finder->prependLocation($views);
                }
            }
        }
    }

    public static function is(self $mode): bool
    {
        return self::get() === $mode;
    }

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
