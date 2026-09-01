<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin\Concerns;

use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\ElementTypes;
use CraftCms\Cms\Plugin\Plugin;

/**
 * @mixin Plugin
 *
 * @internal
 */
trait HasElementTypes
{
    /**
     * Array of element types to register.
     *
     * @var class-string<Element>[]
     */
    protected array $elementTypes = [];

    public function bootHasElementTypes(): void
    {
        $this->app->make(ElementTypes::class)->register(...$this->elementTypes);
    }
}
