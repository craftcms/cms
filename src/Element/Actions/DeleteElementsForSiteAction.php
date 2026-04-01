<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Actions;

use craft\base\ElementInterface;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\Events\AfterDeleteForSite;
use CraftCms\Cms\Element\Events\BeforeDeleteForSite;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/** @internal */
readonly class DeleteElementsForSiteAction
{
    public function __construct(
        private Elements $elements,
        private ResaveElementsAction $resaveElementsAction,
    ) {}

    /**
     * Deletes elements in the site they are currently loaded in.
     *
     * @param  ElementInterface[]  $elements
     *
     * @throws InvalidArgumentException if all elements don’t have the same type and site ID.
     */
    public function handle(array $elements): void
    {
        if (empty($elements)) {
            return;
        }

        // Make sure each element has the same type and site ID
        $firstElement = reset($elements);
        $elementType = $firstElement::class;

        foreach ($elements as $element) {
            if ($element::class !== $elementType || $element->siteId !== $firstElement->siteId) {
                throw new InvalidArgumentException('All elements must have the same type and site ID.');
            }
        }

        // Separate the multi-site elements from the single-site elements
        $multiSiteElementIds = $firstElement::find()
            ->id(array_map(fn (ElementInterface $element) => $element->id, $elements))
            ->status(null)
            ->drafts(null)
            ->siteId(['not', $firstElement->siteId])
            ->unique()
            ->select(['elements.id'])
            ->pluck('id')
            ->all();

        $multiSiteElementIdsIdx = array_flip($multiSiteElementIds);
        $multiSiteElements = [];
        $singleSiteElements = [];

        foreach ($elements as $element) {
            if (isset($multiSiteElementIdsIdx[$element->id])) {
                $multiSiteElements[] = $element;
            } else {
                $singleSiteElements[] = $element;
            }
        }

        if (! empty($multiSiteElements)) {
            foreach ($multiSiteElements as $element) {
                event(new BeforeDeleteForSite($element));
            }

            foreach ($multiSiteElements as $element) {
                $element->beforeDeleteForSite();
            }

            // Delete the rows in elements_sites
            DB::table(Table::ELEMENTS_SITES)
                ->whereIn('elementId', $multiSiteElementIds)
                ->where('siteId', $firstElement->siteId)
                ->delete();

            // Resave them
            $this->resaveElementsAction->handle(
                $firstElement::find()
                    ->id($multiSiteElementIds)
                    ->status(null)
                    ->drafts(null)
                    ->site('*')
                    ->unique(),
                true,
                updateSearchIndex: false,
            );

            foreach ($multiSiteElements as $element) {
                $element->afterDeleteForSite();
            }

            foreach ($multiSiteElements as $element) {
                event(new AfterDeleteForSite($element));
            }
        }

        // Fully delete any single-site elements
        foreach ($singleSiteElements as $element) {
            $this->elements->deleteElement($element, true);
        }
    }
}
