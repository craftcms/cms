<?php

/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use CraftCms\Cms\Element\Concerns\NestedElement;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Field\Contracts\ElementContainerFieldInterface;

/**
 * NestedElementTrait
 *
 * @property ElementInterface|null $primaryOwner the primary owner element
 * @property ElementInterface|null $owner the owner element
 * @property int|null $primaryOwnerId the primary owner element’s ID
 * @property int|null $ownerId the owner element’s ID
 * @property ElementContainerFieldInterface|null $field the element’s field
 * @mixin \CraftCms\Cms\Element\Element
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Concerns\NestedElement} instead.
 * @phpstan-ignore trait.unused
 */
trait NestedElementTrait
{
    use NestedElement;
}
