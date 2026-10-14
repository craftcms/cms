<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Eloquent;

/**
 * What happens to an {@see EloquentElement}'s model when the element is moved to the trash.
 *
 * Either way, the model is deleted once the element is hard-deleted.
 *
 * @since 6.0.0
 */
enum ModelTrashBehavior
{
    /** The model is left alone while the element is in the trash. */
    case Keep;

    /** The model is soft-deleted with the element, and restored with it. Requires the model to use `SoftDeletes`. */
    case SoftDelete;
}
