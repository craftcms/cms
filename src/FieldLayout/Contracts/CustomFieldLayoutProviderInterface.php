<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\Contracts;

use CraftCms\Cms\Field\Contracts\FieldInterface;

interface CustomFieldLayoutProviderInterface extends FieldLayoutProviderInterface
{
    /** @return list<FieldInterface> */
    public function getCustomFields(): array;
}
