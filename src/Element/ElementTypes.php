<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element;

use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Component\TypeRegistry;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Container\Attributes\Singleton;

/**
 * Registers element type classes, keyed internally by their reference handles.
 *
 * ```php
 * public function boot(ElementTypes $elementTypes): void
 * {
 *     $elementTypes->register(MyElement::class);
 * }
 * ```
 *
 * @extends TypeRegistry<ElementInterface>
 */
#[Singleton]
class ElementTypes extends TypeRegistry
{
    protected const string CONTRACT = ElementInterface::class;

    protected const array DEFAULT_TYPES = [
        Address::class,
        Asset::class,
        Entry::class,
        User::class,
    ];

    /** @return class-string<ElementInterface>|null */
    public function typeByRefHandle(string $refHandle): ?string
    {
        return $this->typeByIdentity(strtolower($refHandle));
    }

    /** @param class-string<ElementInterface> $type */
    #[\Override]
    protected function identity(string $type): string
    {
        return strtolower($type::refHandle() ?? $type);
    }
}
