<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin\Concerns;

use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Events\RegisterFieldTypes;
use CraftCms\Cms\Plugin\Plugin;
use Illuminate\Support\Facades\Event;

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

        Event::listen(RegisterFieldTypes::class, function (RegisterFieldTypes $event) {
            $event->types->push(...$this->fieldTypes);
        });
    }
}
