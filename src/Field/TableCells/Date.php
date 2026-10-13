<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\TableCells;

use CraftCms\Cms\Gql\Types\DateTime as GqlDateTime;
use CraftCms\Cms\Support\DateTimeHelper;
use CraftCms\Cms\Support\Query;
use CraftCms\Cms\Ui\Controls\Control;
use CraftCms\Cms\Ui\Controls\Date as DateControl;
use GraphQL\Type\Definition\Type;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
class Date extends TableCell
{
    public static function displayName(): string
    {
        return t('Date');
    }

    protected function createControl(TableCellContext $context): Control
    {
        return DateControl::make($context->path);
    }

    protected function controlValue(TableCellContext $context): ?string
    {
        $date = DateTimeHelper::toDateTime($context->value);

        return $date ? $date->format('Y-m-d') : null;
    }

    public function normalizeValue(mixed $value, bool $fromRequest = false): mixed
    {
        return DateTimeHelper::toDateTime($value) ?: null;
    }

    public function serializeValue(mixed $value, bool $forDb = false): bool|float|int|string|null
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $forDb ? Query::prepareDateForDb($value) : (DateTimeHelper::toIso8601($value) ?: null);
    }

    public function searchKeywords(mixed $value): string
    {
        return '';
    }

    public function gqlType(): Type
    {
        return GqlDateTime::getType();
    }
}
