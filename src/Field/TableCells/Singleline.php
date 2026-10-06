<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\TableCells;

use CraftCms\Cms\Support\Str;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
class Singleline extends TableCell
{
    public static function displayName(): string
    {
        return t('Single-line text');
    }

    public function normalizeValue(mixed $value, bool $fromRequest = false): mixed
    {
        if ($value === null) {
            return null;
        }

        $value = parent::normalizeValue($value, $fromRequest);
        if (! $fromRequest) {
            $value = Str::unescapeShortcodes(Str::shortcodesToEmoji((string) $value));
        }

        return trim(Str::convertLineBreaks((string) $value));
    }
}
