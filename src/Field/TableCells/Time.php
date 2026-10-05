<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\TableCells;

use CraftCms\Cms\Form\Controls\Control;
use CraftCms\Cms\Form\Controls\Time as TimeControl;
use CraftCms\Cms\Support\DateTimeHelper;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
class Time extends Date
{
    public static function displayName(): string
    {
        return t('Time');
    }

    protected function createControl(TableCellContext $context): Control
    {
        return TimeControl::make($context->path);
    }

    protected function controlValue(TableCellContext $context): ?string
    {
        $date = DateTimeHelper::toDateTime($context->value);

        return $date ? $date->format('H:i') : null;
    }
}
