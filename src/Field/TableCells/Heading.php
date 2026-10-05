<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\TableCells;

use CraftCms\Cms\Form\Controls\Control;
use CraftCms\Cms\Form\Controls\Text;
use CraftCms\Cms\Form\Enums\ControlMode;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
class Heading extends TableCell
{
    public static function displayName(): string
    {
        return t('Row heading');
    }

    protected function createControl(TableCellContext $context): Control
    {
        return Text::make($context->path)->mode(ControlMode::ReadOnly);
    }
}
