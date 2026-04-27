<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Operations;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use Illuminate\Container\Attributes\Scoped;
use InvalidArgumentException;

/** @internal */
#[Scoped]
class ElementPlaceholders
{
    private ?array $elements = null;

    private array $uris = [];

    public function setPlaceholderElement(ElementInterface $element): void
    {
        if (! $element->id || ! $element->siteId) {
            throw new InvalidArgumentException('Placeholder element is missing an ID');
        }

        $this->elements[$element->getCanonicalId()][$element->siteId] = $element;

        if ($element->uri) {
            $this->uris[$element->uri][$element->siteId] = $element;
        }
    }

    /**
     * @return ElementInterface[]
     */
    public function getPlaceholderElements(): array
    {
        if (! isset($this->elements)) {
            return [];
        }

        return array_merge(...$this->elements);
    }

    public function getPlaceholderElement(int $sourceId, int $siteId): ?ElementInterface
    {
        return $this->elements[$sourceId][$siteId] ?? null;
    }

    public function getPlaceholderByUri(string $uri, int $siteId): ?ElementInterface
    {
        return $this->uris[$uri][$siteId] ?? null;
    }
}
