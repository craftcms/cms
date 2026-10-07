<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\TableCells;

use CraftCms\Cms\Component\Concerns\MissingComponentTrait;
use CraftCms\Cms\Component\Contracts\MissingComponentInterface;
use CraftCms\Cms\Ui\Contracts\Control;
use CraftCms\Cms\Ui\Controls\Missing;

/**
 * @since 6.0.0
 */
class MissingTableCell extends TableCell implements MissingComponentInterface
{
    use MissingComponentTrait;

    public static function isSelectable(): bool
    {
        return false;
    }

    public function formControl(TableCellContext $context): Control
    {
        return Missing::make($context->path)->provider($this->expectedType);
    }

    public function normalizeValue(mixed $value, bool $fromRequest = false): mixed
    {
        return $value;
    }

    public function getSettings(): array
    {
        return $this->settings ?? [];
    }
}
