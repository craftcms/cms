<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Enums;

use function CraftCms\Cms\t;

/**
 * Whether an image is wider than it is tall, taller than it is wide, or about as wide as it is tall.
 *
 * @since 6.0.0
 */
enum ImageOrientation: string
{
    case Landscape = 'landscape';
    case Portrait = 'portrait';
    case Square = 'square';

    /**
     * How many times the difference between an image’s width and height can go into the larger of them, at most, for it
     * to count as square. 50 means they can differ by up to 2%.
     */
    public const int SQUARE_RATIO = 50;

    /**
     * Returns the orientation of an image with the given dimensions.
     */
    public static function fromDimensions(int $width, int $height): self
    {
        return match (true) {
            abs($width - $height) * self::SQUARE_RATIO <= max($width, $height) => self::Square,
            $width > $height => self::Landscape,
            default => self::Portrait,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Landscape => t('Landscape'),
            self::Portrait => t('Portrait'),
            self::Square => t('Square'),
        };
    }
}
