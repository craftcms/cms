<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use CraftCms\Cms\Component\TypeRegistry;
use CraftCms\Cms\Field\Contracts\ElementContainerFieldInterface;
use Illuminate\Container\Attributes\Singleton;

/**
 * Registers field types that can contain nested entries.
 *
 * ```php
 * public function boot(NestedEntryFieldTypes $fieldTypes): void
 * {
 *     $fieldTypes->register(MyNestedEntryField::class);
 * }
 * ```
 *
 * @extends TypeRegistry<ElementContainerFieldInterface>
 *
 * @since 6.0.0
 */
#[Singleton]
class NestedEntryFieldTypes extends TypeRegistry
{
    protected const string CONTRACT = ElementContainerFieldInterface::class;

    protected const array DEFAULT_TYPES = [Matrix::class];
}
