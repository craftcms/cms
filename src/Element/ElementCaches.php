<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element;

use craft\base\ElementInterface;
use craft\base\NestedElementInterface;
use CraftCms\Cms\View\CacheCollectors\DependencyCollector;
use CraftCms\Cms\View\Data\TemplateCacheContext;
use CraftCms\DependencyAwareCache\Dependency\TagDependency;
use DateTime;
use Illuminate\Container\Attributes\Scoped;
use Throwable;
use yii\base\InvalidConfigException;

#[Scoped]
readonly class ElementCaches
{
    public function __construct(
        private DependencyCollector $dependencyCollector,
    ) {}

    public function isCollectingCacheInfo(): bool
    {
        return $this->dependencyCollector->isCollecting();
    }

    public function startCollectingCacheInfo(): void
    {
        $this->dependencyCollector->begin(new TemplateCacheContext(
            cacheKey: '',
            global: false,
            resources: false,
        ));
    }

    /** @param list<string> $tags */
    public function collectCacheTags(array $tags): void
    {
        $this->dependencyCollector->collectTags($tags);
    }

    public function setCacheExpiryDate(DateTime $expiryDate): void
    {
        $this->dependencyCollector->setExpiryDate($expiryDate);
    }

    public function collectCacheInfoForElement(ElementInterface $element): void
    {
        $this->dependencyCollector->collectElement($element);
    }

    /**
     * @return array{TagDependency|null, int|null}
     */
    public function stopCollectingCacheInfo(): array
    {
        return $this->dependencyCollector->stop();
    }

    /** @return list<string> */
    public function invalidateAll(): array
    {
        return $this->invalidateTags(['element']);
    }

    /**
     * @param  class-string<ElementInterface>  $elementType
     * @return list<string>
     */
    public function invalidateForElementType(string $elementType): array
    {
        return $this->invalidateTags(["element::$elementType"]);
    }

    /**
     * @return list<string>
     */
    public function invalidateForElement(ElementInterface $element): array
    {
        return $this->invalidateTags($this->tagsForElement($element));
    }

    /** @return list<string> */
    private function invalidateTags(array $tags): array
    {
        TagDependency::invalidate($tags);

        return $tags;
    }

    /** @return list<string> */
    private function tagsForElement(ElementInterface $element): array
    {
        $elementClass = $element::class;
        $tags = [
            "element::{$elementClass}::*",
            "element::{$element->id}",
        ];

        $rootElement = $element;

        if ($element instanceof NestedElementInterface) {
            try {
                $owner = $element->getOwner();
            } catch (InvalidConfigException) {
                $owner = null;
            }

            if ($owner) {
                $tags[] = "element::{$owner->id}";

                try {
                    $rootElement = $owner->getRootOwner();
                } catch (Throwable) {
                    $rootElement = $owner;
                }
            }
        }

        if ($rootElement->getIsDraft()) {
            $tags[] = "element::{$elementClass}::drafts";

            return $tags;
        }

        if ($rootElement->getIsRevision()) {
            $tags[] = "element::{$elementClass}::revisions";

            return $tags;
        }

        foreach ($element->getCacheTags() as $tag) {
            if (! str_starts_with($tag, 'element::')) {
                $tag = "element::{$elementClass}::{$tag}";
            }

            $tags[] = $tag;
        }

        return $tags;
    }
}
