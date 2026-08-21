<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Support\CmsAssets;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Html;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

use function CraftCms\Cms\template;

readonly class Icons
{
    private const array LEGACY_ICON_MAP = [
        'alert' => 'triangle-exclamation',
        'asc' => 'arrow-down-short-wide',
        'asset' => 'image',
        'assets' => 'image',
        'circleuarr' => 'circle-arrow-up',
        'collapse' => 'down-left-and-up-right-to-center',
        'condition' => 'diamond',
        'darr' => 'arrow-down',
        'date' => 'calendar',
        'desc' => 'arrow-down-wide-short',
        'disabled' => 'circle-dashed',
        'done' => 'circle-check',
        'downangle' => 'angle-down',
        'draft' => 'scribble',
        'edit' => 'pencil',
        'enabled' => 'circle',
        'expand' => 'up-right-and-down-left-from-center',
        'external' => 'arrow-up-right-from-square',
        'field' => 'pen-to-square',
        'help' => 'circle-question',
        'home' => 'house',
        'info' => 'circle-info',
        'insecure' => 'unlock',
        'larr' => 'arrow-left',
        'layout' => 'table-layout',
        'leftangle' => 'angle-left',
        'listrtl' => 'list-flip',
        'location' => 'location-dot',
        'mail' => 'envelope',
        'menu' => 'bars',
        'move' => 'grip-dots',
        'newstamp' => 'certificate',
        'paperplane' => 'paper-plane',
        'plugin' => 'plug',
        'rarr' => 'arrow-right',
        'refresh' => 'arrows-rotate',
        'remove' => 'xmark',
        'rightangle' => 'angle-right',
        'rotate' => 'rotate-left',
        'routes' => 'signs-post',
        'search' => 'magnifying-glass',
        'secure' => 'lock',
        'settings' => 'gear',
        'shareleft' => 'share-flip',
        'shuteye' => 'eye-slash',
        'sidebar-left' => 'sidebar',
        'sidebar-right' => 'sidebar-flip',
        'structure' => 'list-tree',
        'structurertl' => 'list-tree-flip',
        'template' => 'file-code',
        'time' => 'clock',
        'tool' => 'wrench',
        'uarr' => 'arrow-up',
        'upangle' => 'angle-up',
        'view' => 'eye',
        'wand' => 'wand-magic-sparkles',
    ];

    private const array ORIENTATION_DEPENDENT_ICONS = [
        'sidebar-start' => ['ltr' => 'sidebar', 'rtl' => 'sidebar-flip'],
        'sidebar-end' => ['ltr' => 'sidebar-flip', 'rtl' => 'sidebar'],
    ];

    private const array EARTH_ALIASES = ['world', 'earth'];

    public static function resolveIconName(string $icon): string
    {
        if (isset(self::LEGACY_ICON_MAP[$icon])) {
            return self::LEGACY_ICON_MAP[$icon];
        }

        if (in_array($icon, self::EARTH_ALIASES)) {
            return self::earth();
        }

        if (isset(self::ORIENTATION_DEPENDENT_ICONS[$icon])) {
            $orientation = I18N::getLocale()->getOrientation();

            return self::ORIENTATION_DEPENDENT_ICONS[$icon][$orientation];
        }

        return $icon;
    }

    public static function resolveIconFamily(string $icon): string
    {
        foreach (['custom-icons', 'solid', 'brands', 'regular'] as $family) {
            if (is_file(CmsAssets::resourcesPath("icons/$family/$icon.svg"))) {
                return $family;
            }
        }

        return 'solid';
    }

    /** @return array{name: string, family: string} */
    public static function resolveIconData(string $icon): array
    {
        // Resolve the family from the resolved name, not the raw one.
        $name = self::resolveIconName($icon);

        return [
            'name' => $name,
            'family' => self::resolveIconFamily($name),
        ];
    }

    public static function resolveIconPath(string $icon): string
    {
        $icon = self::resolveIconName($icon);

        return CmsAssets::resourcesPath(sprintf('icons/%s/%s.svg', self::resolveIconFamily($icon), $icon));
    }

    public static function svg(?string $icon, ?string $fallbackLabel = null, ?string $altText = null): ?string
    {
        if ($icon === null) {
            return null;
        }

        static $cache = [];

        $cacheKey = md5(serialize([$icon, $fallbackLabel, $altText]));

        if (array_key_exists($cacheKey, $cache)) {
            return $cache[$cacheKey];
        }

        $attributes = [
            'focusable' => 'false',
        ];

        $icon = self::resolveIconName($icon);

        try {
            if (preg_match('/^[\da-z\-]+$/', $icon)) {
                $path = self::resolveIconPath($icon);

                if (! file_exists($path)) {
                    throw new InvalidArgumentException("Invalid system icon: $icon");
                }

                $svg = file_get_contents($path);
            } else {
                $svg = Html::svg($icon, true, throwException: true);
            }
        } catch (InvalidArgumentException $e) {
            Log::warning("Could not load icon: {$e->getMessage()}", [__METHOD__]);

            if (! $fallbackLabel) {
                return $cache[$cacheKey] = '';
            }

            return $cache[$cacheKey] = self::fallbackSvg($fallbackLabel);
        }

        if ($altText !== null) {
            $attributes['aria'] = ['label' => $altText];
            $attributes['role'] = 'img';
        } else {
            $attributes['aria'] = [
                'hidden' => 'true',
                'labelledby' => false,
            ];
        }

        $svg = preg_replace(Html::TITLE_TAG_RE, '', $svg);

        try {
            $svg = Html::modifyTagAttributes($svg, $attributes);
        } catch (InvalidArgumentException) {
        }

        return $cache[$cacheKey] = $svg;
    }

    public static function fallbackSvg(string $label): string
    {
        static $cache = [];

        return $cache[$label] ??= template('_includes/fallback-icon-svg', [
            'label' => $label,
        ]);
    }

    public static function earth(): string
    {
        $tzGroup = explode('/', Cms::timezone(), 2)[0];

        return match ($tzGroup) {
            'Africa' => 'earth-africa',
            'Asia' => 'earth-asia',
            'Australia' => 'earth-oceania',
            'Europe', 'GMT', 'UTC' => 'earth-europe',
            default => 'earth-americas',
        };
    }
}
