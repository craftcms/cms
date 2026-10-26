<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin\Concerns;

use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\ElementTypes;
use CraftCms\Cms\Element\Eloquent\EloquentElement;
use CraftCms\Cms\Element\Eloquent\EloquentElements;
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
        [$eloquentElementTypes, $elementTypes] = collect($this->elementTypes)
            ->partition(fn (string $elementType) => is_subclass_of($elementType, EloquentElement::class));

        $this->app->make(ElementTypes::class)->register(...$elementTypes);
        $this->app->make(EloquentElements::class)->register(...$eloquentElementTypes);
    }
}
