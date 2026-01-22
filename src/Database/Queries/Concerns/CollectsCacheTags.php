<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Queries\Concerns;

use Craft;
use CraftCms\Cms\Database\Queries\Events\DefineCacheTags;
use CraftCms\Cms\Support\Arr;

/**
 * @internal
 */
trait CollectsCacheTags
{
    /**
     * @var string[]|null
     *
     * @see getCacheTags()
     */
    private ?array $cacheTags = null;

    protected function initCollectsCacheTags(): void
    {
        $this->beforeQuery(function () {
            $this->cacheTags = null;
        });

        $this->afterQuery(function () {
            if (empty($cacheTags = $this->getCacheTags())) {
                return;
            }

            $elementsService = Craft::$app->getElements();

            if ($elementsService->getIsCollectingCacheInfo()) {
                $elementsService->collectCacheTags($cacheTags);
            }
        });
    }

    /**
     * @return string[]
     */
    public function getCacheTags(): array
    {
        if (! is_null($this->cacheTags)) {
            return $this->cacheTags;
        }

        $modelClass = $this->elementType;

        $this->cacheTags = [
            'element',
            "element::{$modelClass}",
        ];

        // If (<= 100) specific IDs were requested, then use those
        if (is_numeric($this->id) ||
            (is_array($this->id) && count($this->id) <= 100 && Arr::isNumeric($this->id))
        ) {
            array_push($this->cacheTags, ...array_map(fn ($id) => "element::$id", Arr::wrap($this->id)));

            return $this->cacheTags;
        }

        $queryTags = $this->cacheTags();

        event($event = new DefineCacheTags($this, $queryTags));

        $queryTags = $event->tags;

        if (! empty($queryTags)) {
            if ($this->drafts !== false) {
                $queryTags[] = 'drafts';
            }

            if ($this->revisions !== false) {
                $queryTags[] = 'revisions';
            }
        } else {
            $queryTags[] = '*';
        }

        foreach ($queryTags as $tag) {
            // tags can be provided fully-formed, or relative to the element type
            if (! str_starts_with((string) $tag, 'element::')) {
                $tag = sprintf('element::%s::%s', $this->elementType, $tag);
            }

            $this->cacheTags[] = $tag;
        }

        return $this->cacheTags;
    }

    /**
     * Returns any cache invalidation tags that caches involving this element query should use as dependencies.
     *
     * Use the most specific tag(s) possible to reduce the likelihood of pointless cache clearing.
     *
     * When elements are created/updated/deleted, their [[ElementInterface::getCacheTags()]] method will be called,
     * and any caches that have those tags listed as dependencies will be invalidated.
     *
     * @return string[]
     */
    protected function cacheTags(): array
    {
        return [];
    }
}
