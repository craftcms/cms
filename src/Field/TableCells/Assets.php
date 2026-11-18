<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\TableCells;

use CraftCms\Cms\Field\Assets as AssetsField;
use Override;

/**
 * Assets cells relate assets that already exist. They can’t upload files, since upload locations belong to fields.
 *
 * @since 6.0.0
 */
class Assets extends BaseRelationCell
{
    public static function fieldType(): string
    {
        return AssetsField::class;
    }

    #[Override]
    protected function fieldConfig(): array
    {
        return [...parent::fieldConfig(), 'allowUploads' => false];
    }
}
