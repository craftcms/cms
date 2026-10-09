<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\TableCells;

use CraftCms\Cms\Ui\Controls\Control;
use CraftCms\Cms\Ui\Controls\Textarea;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
class Multiline extends Singleline
{
    public static function displayName(): string
    {
        return t('Multi-line text');
    }

    protected function createControl(TableCellContext $context): Control
    {
        return Textarea::make($context->path)->rows(1);
    }
}
