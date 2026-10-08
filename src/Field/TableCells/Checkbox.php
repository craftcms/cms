<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\TableCells;

use CraftCms\Cms\Ui\Controls\Checkbox as CheckboxControl;
use CraftCms\Cms\Ui\Controls\Control;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
class Checkbox extends TableCell
{
    public static function displayName(): string
    {
        return t('Checkbox');
    }

    protected function createControl(TableCellContext $context): Control
    {
        return CheckboxControl::make($context->path);
    }
}
