<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\TableCells;

use CraftCms\Cms\Ui\Controls\Control;
use CraftCms\Cms\Ui\Controls\Text;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
class Email extends TableCell
{
    public static function displayName(): string
    {
        return t('Email');
    }

    protected function createControl(TableCellContext $context): Control
    {
        return Text::make($context->path)->inputType('email');
    }

    public function getValueRules(): array
    {
        return ['nullable', 'email'];
    }
}
