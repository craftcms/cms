<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Actions;

use craft\base\ElementInterface;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\ElementCaches;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\Events\AfterPropagateElement;
use CraftCms\Cms\Element\Events\AfterPropagateElements;
use CraftCms\Cms\Element\Events\BeforePropagateElement;
use CraftCms\Cms\Element\Events\BeforePropagateElements;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Element\Queries\Exceptions\QueryAbortedException;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\BulkOps;
use Throwable;

readonly class PropagateElementsAction
{
    public function __construct(
        private ElementCaches $elementCaches,
        private Elements $elements,
        private PropagateElementAction $propagateElementAction,
    ) {}

    /**
     * Propagates all elements that match a given element query to another site(s).
     *
     * @param  ElementQueryInterface  $query  The element query to fetch elements with
     * @param  int|int[]|null  $siteIds  The site ID(s) that the elements should be propagated to. If null, elements will be propagated to all supported sites, except the one they were queried in.
     * @param  bool  $continueOnError  Whether to continue going if an error occurs
     */
    public function handle(
        ElementQueryInterface $query,
        array|int|null $siteIds = null,
        bool $continueOnError = false,
    ): void {
        event(new BeforePropagateElements($query));

        if ($siteIds !== null) {
            // Typecast to integers
            $siteIds = array_map(fn ($siteId) => $siteId, (array) $siteIds);
        }

        BulkOps::ensure(function () use ($query, $siteIds, $continueOnError) {
            $position = 0;

            try {
                $query->each(function (ElementInterface $element) use ($continueOnError, $query, &$position, $siteIds) {
                    /** @var ElementInterface $element */
                    $position++;

                    event(new BeforePropagateElement($query, $element, $position));

                    $element->setScenario(Element::SCENARIO_ESSENTIALS);
                    $supportedSites = Arr::keyBy(ElementHelper::supportedSitesForElement($element), 'siteId');
                    $supportedSiteIds = array_keys($supportedSites);
                    $elementSiteIds = $siteIds !== null ? array_intersect($siteIds,
                        $supportedSiteIds) : $supportedSiteIds;
                    $elementType = $element::class;

                    $e = null;
                    try {
                        $element->newSiteIds = [];

                        foreach ($elementSiteIds as $siteId) {
                            if ($siteId === $element->siteId) {
                                continue;
                            }

                            // Make sure the site element wasn't updated more recently than the main one
                            $siteElement = $this->elements->getElementById($element->id, $elementType, $siteId);
                            if ($siteElement === null || $siteElement->dateUpdated < $element->dateUpdated) {
                                $siteElement ??= false;
                                $this->propagateElementAction->handle($element, $supportedSites, $siteId, $siteElement);
                            }
                        }

                        // It's now fully duplicated and propagated
                        $element->markAsDirty();
                        $element->afterPropagate(false);
                    } catch (Throwable $e) {
                        if (! $continueOnError) {
                            throw $e;
                        }

                        report($e);
                    }

                    event(new AfterPropagateElement($query, $element, $position, $e));

                    // Track this element in bulk operations
                    BulkOps::trackElement($element);

                    // Clear caches
                    $this->elementCaches->invalidateForElement($element);
                });
            } catch (QueryAbortedException) {
                // Fail silently
            }
        });

        event(new AfterPropagateElements($query));
    }
}
