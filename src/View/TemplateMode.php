<?php

declare(strict_types=1);

namespace CraftCms\Cms\View;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Support\Facades\Path;
use CraftCms\Cms\View\Events\RegisterCpTemplateRoots;
use CraftCms\Cms\View\Events\RegisterSiteTemplateRoots;
use Illuminate\Support\Facades\Context;

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
            self::Cp => ['twig', 'html'],
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

    public function templateRoots(): array
    {
        return once(function () {
            $templateRoots = [];

            event($event = match ($this) {
                self::Cp => new RegisterCpTemplateRoots,
                self::Site => new RegisterSiteTemplateRoots,
            });

            foreach ($event->roots as $templatePath => $dir) {
                $templatePath = strtolower(trim($templatePath, '/'));

                $templateRoots[$templatePath] ??= [];

                array_push($templateRoots[$templatePath], ...(array) $dir);
            }

            // Longest (most specific) first
            krsort($templateRoots, SORT_STRING);

            return $templateRoots;
        });
    }
}
