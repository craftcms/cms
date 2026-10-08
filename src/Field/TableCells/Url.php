<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\TableCells;

use CraftCms\Cms\Ui\Controls\Control;
use CraftCms\Cms\Ui\Controls\Text;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
class Url extends TableCell
{
    public static function displayName(): string
    {
        return t('URL');
    }

    protected function createControl(TableCellContext $context): Control
    {
        return Text::make($context->path)->inputType('url');
    }

    public function getValueRules(): array
    {
        return ['nullable', 'url'];
    }
}
