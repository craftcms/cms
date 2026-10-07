<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\TableCells;

use CraftCms\Cms\Ui\Controls\Control;
use CraftCms\Cms\Ui\Controls\Lightswitch as LightswitchControl;
use GraphQL\Type\Definition\Type;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
class Lightswitch extends TableCell
{
    public static function displayName(): string
    {
        return t('Lightswitch');
    }

    protected function createControl(TableCellContext $context): Control
    {
        return LightswitchControl::make($context->path);
    }

    public function gqlType(): Type
    {
        return Type::boolean();
    }
}
