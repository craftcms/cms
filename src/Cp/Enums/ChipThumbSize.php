<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Enums;

use CraftCms\Cms\Cp\Html\ElementHtml;

use function CraftCms\Cms\t;

/**
 * The thumbnail sizes a chip can have, overriding the one its size gives it.
 * Mirrors `ChipThumbSize` in `@craftcms/ui` (src/components/chip/chip.ts).
 *
 * @since 6.0.0
 */
enum ChipThumbSize: string
{
    case Sm = 'sm';
    case Md = 'md';
    case Lg = 'lg';
    case Xl = 'xl';
    case Xxl = '2xl';

    /**
     * The thumbnail's edge length in pixels, or `null` for `sm`, which keeps
     * the thumbnail a small chip gives it.
     */
    public function pixels(): ?int
    {
        return match ($this) {
            self::Sm => null,
            self::Md => 60,
            self::Lg => 120,
            self::Xl => 180,
            self::Xxl => 240,
        };
    }

    /**
     * The sizes an element index offers, by view: table rows stop at a
     * card's thumbnail size, and thumbnail tiles come in every size.
     */
    public const array INDEX_VIEW_SIZES = [
        'table' => ['sm', 'md', 'lg'],
        'thumbs' => ['sm', 'md', 'lg', 'xl', '2xl'],
    ];

    /**
     * An index's sizes by view, as a source saved them, else small rows and
     * tiles the size of a card's thumbnail.
     *
     * @return array{table: string, thumbs: string}
     */
    public static function indexDefaults(mixed $saved): array
    {
        $saved = is_array($saved) ? $saved : [];
        $sizes = ['table' => 'sm', 'thumbs' => 'lg'];

        foreach ($sizes as $view => $size) {
            $sizes[$view] = in_array($saved[$view] ?? null, self::INDEX_VIEW_SIZES[$view], true) ? $saved[$view] : $size;
        }

        return $sizes;
    }

    public function label(): string
    {
        return match ($this) {
            self::Sm => t('Small'),
            self::Md => t('Medium'),
            self::Lg => t('Large'),
            self::Xl => t('XL'),
            self::Xxl => t('2XL'),
        };
    }

    /** The chip size that suits the thumbnail, as `craft-chip` picks it. */
    public function chipSize(): string
    {
        return match ($this) {
            self::Sm => ElementHtml::CHIP_SIZE_SMALL,
            self::Md => ElementHtml::CHIP_SIZE_MEDIUM,
            default => ElementHtml::CHIP_SIZE_LARGE,
        };
    }
}
