<?php

declare(strict_types=1);

namespace CraftCms\Cms\Shared\Enums;

use InvalidArgumentException;

enum Color: string
{
    case Red = 'red';
    case Orange = 'orange';
    case Amber = 'amber';
    case Yellow = 'yellow';
    case Lime = 'lime';
    case Green = 'green';
    case Emerald = 'emerald';
    case Teal = 'teal';
    case Cyan = 'cyan';
    case Sky = 'sky';
    case Blue = 'blue';
    case Indigo = 'indigo';
    case Violet = 'violet';
    case Purple = 'purple';
    case Fuchsia = 'fuchsia';
    case Pink = 'pink';
    case Rose = 'rose';
    case White = 'white';
    case Gray = 'gray';
    case Black = 'black';

    /**
     * Returns the color associated with a given status name, if known.
     */
    public static function tryFromStatus(string $status): ?self
    {
        return match ($status) {
            'on', 'live', 'active', 'enabled', 'turquoise' => self::Teal,
            'off', 'suspended', 'expired' => self::Red,
            'warning' => self::Amber,
            'pending' => self::Orange,
            'grey' => self::Gray,
            default => self::tryFrom($status),
        };
    }

    /**
     * Returns the color’s CSS `var()` property for a given shade (50, 100, 200, ... 900).
     */
    public function cssVar(int $shade): string
    {
        // make sure it's a valid shade
        if (! in_array($shade, [50, ...range(100, 900, 100)])) {
            throw new InvalidArgumentException("Invalid color shade: $shade");
        }

        return match ($this) {
            self::White, self::Gray, self::Black => sprintf('var(--color-%s)', $this->value),
            default => sprintf('var(--color-%s-%s)', $this->value, str_pad((string) $shade, 3, '0', STR_PAD_LEFT)),
        };
    }
}
