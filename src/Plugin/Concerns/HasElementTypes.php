<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin\Concerns;

use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Events\RegisterElementTypes;
use CraftCms\Cms\Plugin\Plugin;
use Illuminate\Support\Facades\Event;

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
        if (! $this->elementTypes) {
            return;
        }

        Event::listen(function (RegisterElementTypes $event) {
            array_push($event->types, ...$this->elementTypes);
        });
    }
}
