<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use CraftCms\Cms\Component\TypeRegistry;
use CraftCms\Cms\Field\LinkTypes\Asset;
use CraftCms\Cms\Field\LinkTypes\BaseLinkType;
use CraftCms\Cms\Field\LinkTypes\Email;
use CraftCms\Cms\Field\LinkTypes\Entry;
use CraftCms\Cms\Field\LinkTypes\Phone;
use CraftCms\Cms\Field\LinkTypes\Sms;
use CraftCms\Cms\Field\LinkTypes\Url;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Collection;

/**
 * Registers link type classes, keyed internally by their IDs.
 *
 * ```php
 * public function boot(LinkTypes $linkTypes): void
 * {
 *     $linkTypes->register(MyLinkType::class);
 * }
 * ```
 *
 * @extends TypeRegistry<BaseLinkType>
 */
#[Singleton]
class LinkTypes extends TypeRegistry
{
    protected const string CONTRACT = BaseLinkType::class;

    protected const array DEFAULT_TYPES = [
        Asset::class,
        Email::class,
        Entry::class,
        Phone::class,
        Sms::class,
        Url::class,
    ];

    protected const array PROTECTED_TYPES = [Url::class];

    /** @return Collection<string, class-string<BaseLinkType>> */
    public function typesById(): Collection
    {
        $types = $this->typesByIdentity();
        $types->forget(Url::id());

        return $types->put(Url::id(), Url::class);
    }

    /** @param class-string<BaseLinkType> $type */
    #[\Override]
    protected function identity(string $type): string
    {
        return $type::id();
    }
}
