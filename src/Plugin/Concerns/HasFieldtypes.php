<?php

namespace CraftCms\Cms\Plugin\Concerns;

use craft\base\Event as YiiEvent;
use craft\base\FieldInterface;
use craft\events\RegisterComponentTypesEvent;
use craft\services\Fields;
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
        if (! $this->fieldTypes) {
            return;
        }

        /** @todo: Laravelize */
        YiiEvent::on(
            Fields::class,
            Fields::EVENT_REGISTER_FIELD_TYPES,
            function (RegisterComponentTypesEvent $event) {
                array_push($event->types, ...$this->fieldTypes);
            }
        );
    }
}
