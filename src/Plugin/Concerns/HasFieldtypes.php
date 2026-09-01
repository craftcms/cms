<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin\Concerns;

use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\FieldTypes;
use CraftCms\Cms\Plugin\Plugin;

/**
 * @mixin Plugin
 *
 * @internal
 */
trait HasFieldtypes
{
    /**
     * Array of field types to register.
     *
     * @var class-string<FieldInterface>[]
     */
    protected array $fieldTypes = [];

    public function bootHasFieldTypes(): void
    {
        $this->app->make(FieldTypes::class)->register(...$this->fieldTypes);
    }
}
