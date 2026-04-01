<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Actions;

use craft\base\ElementInterface;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Element\Events\AfterResaveElement;
use CraftCms\Cms\Element\Events\AfterResaveElements;
use CraftCms\Cms\Element\Events\BeforeResaveElement;
use CraftCms\Cms\Element\Events\BeforeResaveElements;
use CraftCms\Cms\Element\Exceptions\InvalidElementException;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Element\Queries\Exceptions\QueryAbortedException;
use CraftCms\Cms\Support\Facades\BulkOps;
use Throwable;

/** @internal */
readonly class ResaveElementsAction
{
    public function __construct(
        private SaveElementAction $saveElementAction
    ) {}

    public function handle(
        ElementQueryInterface $query,
        bool $continueOnError = false,
        bool $skipRevisions = true,
        ?bool $updateSearchIndex = null,
        bool $touch = false,
    ): void {
        event(new BeforeResaveElements($query));

        BulkOps::ensure(function () use ($query, $skipRevisions, $touch, $updateSearchIndex, $continueOnError) {
            $position = 0;

            try {
                $query->each(function (ElementInterface $element) use ($continueOnError, $query, &$position, $skipRevisions, $touch, $updateSearchIndex) {
                    /** @var ElementInterface $element */
                    $position++;

                    $element->setScenario(Element::SCENARIO_ESSENTIALS);
                    $element->resaving = true;

                    $e = null;
                    try {
                        event(new BeforeResaveElement($query, $element, $position));

                        // Make sure this isn't a revision
                        if ($skipRevisions) {
                            $label = $element->getUiLabel();
                            $label = $label !== '' ? "$label ($element->id)" : sprintf('%s %s',
                                $element::lowerDisplayName(), $element->id);
                            try {
                                if (ElementHelper::isRevision($element)) {
                                    throw new InvalidElementException($element, "Skipped resaving $label because it's a revision.");
                                }
                            } catch (Throwable $rootException) {
                                throw new InvalidElementException($element, "Skipped resaving $label due to an error obtaining its root element: ".$rootException->getMessage());
                            }
                        }

                        $this->saveElementAction->handle(
                            element: $element,
                            updateSearchIndex: $updateSearchIndex,
                            forceTouch: $touch,
                            saveContent: true,
                        );
                    } catch (Throwable $e) {
                        if (! $continueOnError) {
                            throw $e;
                        }

                        report($e);
                    }

                    event(new AfterResaveElement($query, $element, $position, $e));
                });
                /** @phpstan-ignore-next-line */
            } catch (QueryAbortedException) {
                // Fail silently
            }
        });

        event(new AfterResaveElements($query));
    }
}
