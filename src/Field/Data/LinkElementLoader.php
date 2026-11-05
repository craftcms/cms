<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Data;

use CraftCms\Cms\Field\Link;
use CraftCms\Cms\Field\LinkTypes\BaseElementLinkType;

/**
 * Fetches the elements of a group of links together the first time any of them needs its element.
 *
 * @internal
 *
 * @since 6.0.0
 */
class LinkElementLoader
{
    private bool $loaded = false;

    /**
     * @param  list<LinkData>  $links
     */
    public function __construct(
        private readonly array $links,
    ) {}

    /**
     * Shares a loader between the given links that link to elements.
     *
     * @param  iterable<mixed>  $values
     */
    public static function attach(iterable $values): void
    {
        $links = [];
        foreach ($values as $value) {
            if ($value instanceof LinkData && is_subclass_of(Link::types()[$value->getType()] ?? null, BaseElementLinkType::class)) {
                $links[] = $value;
            }
        }

        if (! $links) {
            return;
        }

        $loader = new self($links);
        foreach ($links as $link) {
            $link->setElementLoader($loader);
        }
    }

    public function load(): void
    {
        if ($this->loaded) {
            return;
        }

        $this->loaded = true;

        $valuesByType = [];
        foreach ($this->links as $link) {
            $valuesByType[$link->getType()][] = $link->serialize()['value'] ?? '';
        }

        $types = Link::types();
        foreach ($valuesByType as $typeId => $values) {
            /** @var class-string<BaseElementLinkType> $type */
            $type = $types[$typeId];
            $type::preloadElements($values);
        }
    }
}
