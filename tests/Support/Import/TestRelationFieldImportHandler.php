<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\Support\Import;

use CraftCms\Cms\Field\BaseRelationField;
use Override;

/**
 * The test field import handler, registered for relation fields.
 */
class TestRelationFieldImportHandler extends TestFieldImportHandler
{
    #[Override]
    public static function fieldClass(): string
    {
        return BaseRelationField::class;
    }
}
