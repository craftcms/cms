<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Enums;

use CraftCms\Cms\Cp\Html\ElementHtml;

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
