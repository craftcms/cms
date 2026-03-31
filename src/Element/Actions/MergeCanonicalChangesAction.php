<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Actions;

use craft\base\ElementInterface;
use CraftCms\Cms\Element\BulkOp\BulkOps;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Element\Events\AfterMergeCanonicalChanges;
use CraftCms\Cms\Element\Events\BeforeMergeCanonicalChanges;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\DateTimeHelper;
use Exception;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/** @internal */
readonly class MergeCanonicalChangesAction
{
    public function __construct(
        private BulkOps $bulkOps,
        private SaveElementAction $saveElementAction,
    ) {}

    public function handle(ElementInterface $element): void
    {
        if ($element->getIsCanonical()) {
            throw new InvalidArgumentException('Only a derivative element can be passed to '.__METHOD__);
        }

        if (! $element::trackChanges()) {
            throw new InvalidArgumentException($element::class.' elements don’t track their changes');
        }

        // Make sure the derivative element actually supports its own site ID
        $supportedSites = Arr::keyBy(ElementHelper::supportedSitesForElement($element), 'siteId');
        if (! isset($supportedSites[$element->siteId])) {
            throw new Exception('Attempting to merge source changes for a draft in an unsupported site.');
        }

        event(new BeforeMergeCanonicalChanges($element));

        $this->bulkOps->ensure(function () use ($element, $supportedSites) {
            DB::transaction(function () use ($element, $supportedSites) {
                // Start with the other sites (if any), so we don't update dateLastMerged until the end
                $otherSiteIds = array_keys(Arr::except($supportedSites, $element->siteId));
                if (! empty($otherSiteIds)) {
                    $siteElements = $element->getLocalizedQuery()
                        ->siteId($otherSiteIds)
                        ->status(null)
                        ->all();
                } else {
                    $siteElements = [];
                }

                foreach ($siteElements as $siteElement) {
                    $siteElement->mergeCanonicalChanges();
                    $siteElement->mergingCanonicalChanges = true;
                    $this->saveElementAction->handle(
                        element: $siteElement,
                        runValidation: false,
                        propagate: false,
                        supportedSites: $supportedSites,
                    );
                }

                // Now the $element’s site
                $element->mergeCanonicalChanges();
                $duplicateOf = $element->duplicateOf;
                $element->duplicateOf = null;
                $element->dateLastMerged = DateTimeHelper::now();
                $element->mergingCanonicalChanges = true;
                $this->saveElementAction->handle(
                    element: $element,
                    runValidation: false,
                    propagate: false,
                    supportedSites: $supportedSites,
                );
                $element->duplicateOf = $duplicateOf;

                // It's now fully merged and propagated
                $element->afterPropagate(false);
            });

            $element->mergingCanonicalChanges = false;
        });

        event(new AfterMergeCanonicalChanges($element));
    }
}
