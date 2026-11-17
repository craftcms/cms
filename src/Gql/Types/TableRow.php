<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Types;

use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Field\Table as TableField;
use CraftCms\Cms\Field\TableCells\BaseRelationCell;
use CraftCms\Cms\Field\TableCellTypes;
use CraftCms\Cms\Support\Arr;
use GraphQL\Type\Definition\InputType;
use GraphQL\Type\Definition\OutputType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use InvalidArgumentException;
use Override;

/**
 * @phpstan-import-type TableColumn from TableField
 *
 * @since 6.0.0
 */
class TableRow extends ObjectType
{
    #[Override]
    protected function resolve(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): mixed
    {
        $fieldName = $resolveInfo->fieldName;
        $value = $source[$fieldName];

        if (! $value instanceof ElementQueryInterface) {
            return $value;
        }

        /** @var array<string, TableColumn> $columns */
        $columns = $this->config['columns'] ?? [];
        $column = $columns[$fieldName] ?? Arr::first($columns, fn (array $column) => ($column['handle'] ?? null) === $fieldName);
        $cell = $column !== null ? app(TableCellTypes::class)->create($column) : null;

        // Relation cells return only the elements the schema allows
        return $cell instanceof BaseRelationCell ? $cell->gqlValue($value, $resolveInfo, is_array($context) ? $context : null) : [];
    }

    /**
     * @param  bool  $includeHandles  Whether columns also should be present by their field handles.
     * @param  array<string, TableColumn>  $columns
     * @return array<string, Type&(OutputType|InputType)>
     */
    public static function prepareRowFieldDefinition(array $columns, bool $includeHandles = true, bool $input = false): array
    {
        $contentFields = [];

        foreach ($columns as $columnKey => $columnDefinition) {
            $cell = app(TableCellTypes::class)->create($columnDefinition);
            if ($input) {
                $cellType = $cell->gqlInputType();

                if (! $cellType instanceof InputType) {
                    throw new InvalidArgumentException('Table cell types must provide a GraphQL type for the requested direction.');
                }
            } else {
                $cellType = $cell->gqlType();

                if (! $cellType instanceof OutputType) {
                    throw new InvalidArgumentException('Table cell types must provide a GraphQL type for the requested direction.');
                }
            }

            $contentFields[$columnKey] = $cellType;

            if ($includeHandles && ! empty($columnDefinition['handle'])) {
                $contentFields[$columnDefinition['handle']] = $cellType;
            }
        }

        return $contentFields;
    }
}
