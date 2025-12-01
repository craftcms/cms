<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin\Concerns;

use craft\base\Element;
use craft\base\Event as YiiEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\services\Elements;
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
        if (! $this->elementTypes) {
            return;
        }

        /** @todo: Laravelize */
        YiiEvent::on(
            Elements::class,
            Elements::EVENT_REGISTER_ELEMENT_TYPES,
            function (RegisterComponentTypesEvent $event) {
                array_push($event->types, ...$this->elementTypes);
            }
        );
    }
}
