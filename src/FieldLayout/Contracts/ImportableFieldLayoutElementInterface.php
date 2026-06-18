<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\Contracts;

use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\FieldLayout\FieldLayout;

interface ImportableFieldLayoutElementInterface
{
    public function getFieldsForMapping(FieldLayout $fieldLayout, ?FieldInterface $ownerField, mixed $provider, ?string $prefix = null): array;
}
