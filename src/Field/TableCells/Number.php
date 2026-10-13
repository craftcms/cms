<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\TableCells;

use CraftCms\Cms\Gql\Types\Number as GqlNumber;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Ui\Controls\Control;
use CraftCms\Cms\Ui\Controls\Number as NumberControl;
use GraphQL\Type\Definition\Type;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
class Number extends TableCell
{
    public static function displayName(): string
    {
        return t('Number');
    }

    protected function createControl(TableCellContext $context): Control
    {
        return NumberControl::make($context->path);
    }

    public function normalizeValue(mixed $value, bool $fromRequest = false): mixed
    {
        if (is_array($value) && isset($value['locale'], $value['value'])) {
            return I18N::normalizeNumber($value['value'], $value['locale']);
        }

        return parent::normalizeValue($value, $fromRequest);
    }

    public function gqlType(): Type
    {
        return GqlNumber::getType();
    }
}
