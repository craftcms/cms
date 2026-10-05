<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\TableCells;

use CraftCms\Cms\Field\Data\ColorData;
use CraftCms\Cms\Form\Controls\Color as ColorControl;
use CraftCms\Cms\Form\Controls\Control;
use CraftCms\Cms\Validation\Rules\ColorRule;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
class Color extends TableCell
{
    public static function displayName(): string
    {
        return t('Color');
    }

    protected function createControl(TableCellContext $context): Control
    {
        return ColorControl::make($context->path);
    }

    public function normalizeValue(mixed $value, bool $fromRequest = false): mixed
    {
        if ($value instanceof ColorData) {
            return $value;
        }

        if (! $value || $value === '#') {
            return null;
        }

        $value = strtolower((string) parent::normalizeValue($value, $fromRequest));
        if ($value[0] !== '#') {
            $value = '#'.$value;
        }
        if (strlen($value) === 4) {
            $value = '#'.$value[1].$value[1].$value[2].$value[2].$value[3].$value[3];
        }

        return new ColorData($value);
    }

    public function serializeValue(mixed $value, bool $forDb = false): bool|float|int|string|null
    {
        return $value instanceof ColorData ? $value->getHex() : parent::serializeValue($value, $forDb);
    }

    public function getValueRules(): array
    {
        return ['nullable', new ColorRule];
    }
}
