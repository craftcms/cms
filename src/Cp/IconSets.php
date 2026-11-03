<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp;

use Closure;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Cp\IconSets\DirectoryIconSet;
use CraftCms\Cms\Cp\IconSets\IconSet;
use Illuminate\Container\Attributes\Singleton;
use InvalidArgumentException;

use function CraftCms\Cms\t;

/**
 * Manages the custom icon sets that icon pickers can offer alongside Font Awesome.
 *
 * Custom icon sets are registered via {@see register()}, or the <config5:customIconPath> config setting.
 *
 * ```php
 * public function boot(IconSets $iconSets): void
 * {
 *     $iconSets->register(new DirectoryIconSet('my-icons', 'My Icons', __DIR__.'/icons'));
 * }
 * ```
 *
 * @since 6.0.0
 */
#[Singleton]
class IconSets
{
    /**
     * The handle that refers to the built-in Font Awesome icons, when configuring which sets an icon picker should show.
     *
     * Font Awesome icons’ values aren’t prefixed with it.
     */
    public const string FONT_AWESOME = 'fontawesome';

    /** The handle of the icon set registered via the <config5:customIconPath> config setting. */
    public const string CUSTOM = 'custom';

    /** @var array<string, IconSet> */
    private array $iconSets = [];

    /** @var list<Closure(self): void> */
    private array $deferred = [];

    private bool $resolved = false;

    /**
     * Parses an icon value into its set handle and icon name, if it references a custom icon set.
     *
     * @param  string  $value  The icon value, e.g. `my-icons:arrow`
     * @return array{string, string}|null The set handle and icon name, or `null` if the value doesn’t reference a custom icon set
     */
    public static function parse(string $value): ?array
    {
        // Icon names can't contain slashes, so file paths (`C:\...`) won't match
        if (! preg_match('/^([a-zA-Z][\w\-]*):([^\/\\\\<>:]+)$/', $value, $match)) {
            return null;
        }

        return [$match[1], $match[2]];
    }

    /**
     * Registers custom icon sets.
     *
     * An icon set replaces any previously-registered icon set with the same handle.
     *
     * @throws InvalidArgumentException if an icon set has an invalid handle
     */
    public function register(IconSet ...$iconSets): void
    {
        foreach ($iconSets as $iconSet) {
            if (
                ! preg_match('/^[a-zA-Z][\w\-]*$/', $iconSet->handle) ||
                $iconSet->handle === self::FONT_AWESOME
            ) {
                throw new InvalidArgumentException("Invalid icon set handle: $iconSet->handle");
            }

            $this->iconSets[$iconSet->handle] = $iconSet;
        }
    }

    /**
     * Defers a callback until the icon sets are first read, for registration that
     * needs request state. It runs once.
     *
     * @param  Closure(self): void  $callback
     */
    public function defer(Closure $callback): void
    {
        $this->deferred[] = $callback;
    }

    /**
     * Returns all registered custom icon sets, indexed by their handles.
     *
     * @return array<string, IconSet>
     */
    public function getIconSets(): array
    {
        if (! $this->resolved) {
            $this->resolved = true;

            $customIconPath = Cms::config()->customIconPath;

            if ($customIconPath) {
                $this->iconSets = [
                    self::CUSTOM => new DirectoryIconSet(self::CUSTOM, t('Custom'), $customIconPath),
                ] + $this->iconSets;
            }

            foreach ($this->deferred as $callback) {
                $callback($this);
            }

            $this->deferred = [];
        }

        return $this->iconSets;
    }

    /**
     * Returns a custom icon set by its handle.
     */
    public function getIconSetByHandle(string $handle): ?IconSet
    {
        return $this->getIconSets()[$handle] ?? null;
    }

    /**
     * Returns the names of all available icon sets, including Font Awesome, indexed by their handles.
     *
     * @return array<string, string>
     */
    public function getIconSetNames(): array
    {
        $names = [self::FONT_AWESOME => 'Font Awesome'];

        foreach ($this->getIconSets() as $handle => $iconSet) {
            $names[$handle] = $iconSet->name;
        }

        return $names;
    }
}
