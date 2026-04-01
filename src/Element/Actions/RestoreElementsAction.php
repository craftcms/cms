<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Actions;

use craft\base\ElementInterface;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\ElementCaches;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Element\Events\AfterRestoreElement;
use CraftCms\Cms\Element\Events\BeforeRestoreElement;
use CraftCms\Cms\Element\Exceptions\UnsupportedSiteException;
use CraftCms\Cms\Search\Search;
use CraftCms\Cms\Support\Arr;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/** @internal */
readonly class RestoreElementsAction
{
    public function __construct(
        private CascadeDeleteDraftsAndRevisionsAction $cascadeDeleteDraftsAndRevisionsAction,
        private ElementCaches $elementCaches,
        private Search $search,
    ) {}

    /**
     * Restores multiple elements.
     *
     * @param  ElementInterface[]  $elements
     * @return bool Whether at least one element was restored successfully
     *
     * @throws UnsupportedSiteException if an element is being restored for a site it doesn’t support
     */
    public function handle(array $elements): bool
    {
        // Fire "before" events
        foreach ($elements as $element) {
            event(new BeforeRestoreElement($element));

            if (! $element->beforeRestore()) {
                return false;
            }
        }

        DB::beginTransaction();

        try {
            // Restore the elements
            foreach ($elements as $element) {
                // Get the sites supported by this element
                $supportedSites = Arr::keyBy(ElementHelper::supportedSitesForElement($element), 'siteId');
                if (empty($supportedSites)) {
                    throw new UnsupportedSiteException($element, $element->siteId,
                        "Element $element->id has no supported sites.");
                }

                // Make sure the element actually supports the site it's being saved in
                if (! isset($supportedSites[$element->siteId])) {
                    throw new UnsupportedSiteException($element, $element->siteId,
                        'Attempting to restore an element in an unsupported site.');
                }

                // Get the element in each supported site
                $otherSiteIds = array_keys(Arr::except($supportedSites, $element->siteId));

                if (! empty($otherSiteIds)) {
                    $siteElements = $element->getLocalizedQuery()
                        ->siteId($otherSiteIds)
                        ->status(null)
                        ->trashed(null)
                        ->all();
                } else {
                    $siteElements = [];
                }

                // Make sure it still passes essential validation
                $element->setScenario(Element::SCENARIO_ESSENTIALS);
                if (! $element->validate()) {
                    Log::warning("Unable to restore element $element->id: doesn't pass essential validation: ".print_r($element->errors, true), [__METHOD__]);
                    DB::rollBack();

                    return false;
                }

                foreach ($siteElements as $siteElement) {
                    if ($siteElement === $element) {
                        continue;
                    }

                    $siteElement->setScenario(Element::SCENARIO_ESSENTIALS);

                    if (! $siteElement->validate()) {
                        Log::warning("Unable to restore element $element->id: doesn't pass essential validation for site $element->siteId: ".print_r($element->errors, true), [__METHOD__]);
                        throw new Exception("Element $element->id doesn't pass essential validation for site $element->siteId.");
                    }
                }

                // Restore it
                DB::table(Table::ELEMENTS)
                    ->where('id', $element->id)
                    ->update([
                        'dateDeleted' => null,
                        'dateUpdated' => now(),
                        'deletedWithOwner' => null,
                    ]);

                // Also restore the element’s drafts & revisions
                $this->cascadeDeleteDraftsAndRevisionsAction->handle($element->id, false);

                // Restore its search indexes
                $this->search->indexElementAttributes($element);
                foreach ($siteElements as $siteElement) {
                    $this->search->indexElementAttributes($siteElement);
                }

                // Invalidate caches
                $this->elementCaches->invalidateForElement($element);
            }

            // Fire "after" events
            foreach ($elements as $element) {
                $element->afterRestore();
                $element->trashed = false;
                $element->dateDeleted = null;
                $element->deletedWithOwner = null;

                event(new AfterRestoreElement($element));
            }

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            throw $e;
        }

        return true;
    }
}
