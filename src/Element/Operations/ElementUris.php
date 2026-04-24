<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Operations;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\ElementCaches;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\Events\AfterUpdateSlugAndUri;
use CraftCms\Cms\Element\Events\BeforeUpdateSlugAndUri;
use CraftCms\Cms\Element\Events\SetElementUri;
use CraftCms\Cms\Element\Jobs\UpdateElementSlugsAndUris;
use CraftCms\Cms\Shared\Exceptions\OperationAbortedException;
use CraftCms\Cms\Site\Sites;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Facades\DB;

/** @internal */
#[Singleton]
readonly class ElementUris
{
    public function __construct(
        private Elements $elements,
        private ElementCaches $elementCaches,
        private Sites $sites,
    ) {}

    /**
     * @throws OperationAbortedException
     */
    public function setElementUri(ElementInterface $element): void
    {
        event($event = new SetElementUri($element));

        if ($event->handled) {
            return;
        }

        ElementHelper::setUniqueUri($element);
    }

    /**
     * @throws OperationAbortedException
     */
    public function updateElementSlugAndUri(
        ElementInterface $element,
        bool $updateOtherSites = true,
        bool $updateDescendants = true,
        bool $queue = false,
    ): void {
        if ($queue) {
            dispatch(new UpdateElementSlugsAndUris(
                $element::class,
                $element->id,
                $element->siteId,
                $updateOtherSites,
                $updateDescendants,
            ));

            return;
        }

        if ($element::hasUris()) {
            $this->setElementUri($element);
        }

        event(new BeforeUpdateSlugAndUri($element));

        DB::table(Table::ELEMENTS_SITES)
            ->where('elementId', $element->id)
            ->where('siteId', $element->siteId)
            ->update([
                'slug' => $element->slug,
                'uri' => $element->uri,
                'dateUpdated' => now(),
            ]);

        event(new AfterUpdateSlugAndUri($element));

        $this->elementCaches->invalidateForElement($element);

        if ($updateOtherSites) {
            $this->updateElementSlugAndUriInOtherSites($element);
        }

        if ($updateDescendants) {
            $this->updateDescendantSlugsAndUris($element, $updateOtherSites);
        }
    }

    public function updateElementSlugAndUriInOtherSites(ElementInterface $element): void
    {
        foreach ($this->sites->getAllSiteIds() as $siteId) {
            if ($siteId === $element->siteId) {
                continue;
            }

            $elementInOtherSite = $element->getLocalizedQuery()
                ->siteId($siteId)
                ->one();

            if ($elementInOtherSite) {
                $this->updateElementSlugAndUri($elementInOtherSite, false, false);
            }
        }
    }

    public function updateDescendantSlugsAndUris(
        ElementInterface $element,
        bool $updateOtherSites = true,
        bool $queue = false,
    ): void {
        $query = $this->elements->createElementQuery($element::class)
            ->descendantOf($element)
            ->descendantDist(1)
            ->status(null)
            ->siteId($element->siteId);

        if ($queue) {
            $childIds = $query->ids();

            if (! empty($childIds)) {
                dispatch(new UpdateElementSlugsAndUris(
                    elementType: $element::class,
                    elementId: $childIds,
                    siteId: $element->siteId,
                    updateOtherSites: $updateOtherSites,
                    updateDescendants: true,
                ));
            }

            return;
        }

        $query->each(fn (ElementInterface $child) => $this->updateElementSlugAndUri(
            element: $child,
            updateOtherSites: $updateOtherSites,
            updateDescendants: true,
            queue: false,
        ));
    }
}
