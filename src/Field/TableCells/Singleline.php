<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\TableCells;

use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Ui\Controls\Control;
use CraftCms\Cms\Ui\Controls\Textarea;

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

        return trim($this->normalizeLineBreaks((string) $value));
    }

    protected function createControl(TableCellContext $context): Control
    {
        return Textarea::make($context->path)->rows(1)->singleLine();
    }

    /**
     * Replaces line breaks with spaces, so values stay on one line.
     */
    protected function normalizeLineBreaks(string $value): string
    {
        return preg_replace('/\R/u', ' ', $value);
    }
}
