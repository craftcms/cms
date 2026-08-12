<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Queries\Concerns;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Contracts\ExpirableElementInterface;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Element\Queries\Events\ElementHydrated;
use CraftCms\Cms\Element\Queries\Events\ElementHydrating;
use CraftCms\Cms\Element\Queries\Events\ElementsHydrated;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\View\CacheCollectors\DependencyCollector;
use Illuminate\Support\Collection;
use stdClass;

/**
 * @template TValue of ElementInterface
 *
 * @internal
 */
trait HydratesElements
{
    /**
     * Create a collection of elements from plain arrays.
     *
     * @param  stdClass[]  $items
     * @return TValue[]|array<array<string, mixed>>
     */
    public function hydrate(array $items): array
    {
        $items = array_map(fn (stdClass $row) => (array) $row, $items);

        if ($this->asArray) {
            return $items;
        }

        $elements = collect($items)
            ->when($this->searchResults, fn (Collection $collection) => $collection->map(function (array $row) {
                if (! isset($row['id'], $row['siteId'])) {
                    return $row;
                }

                $key = sprintf('%s-%s', $row['id'], $row['siteId']);

                if (isset($this->searchResults[$key])) {
                    $row['searchScore'] = (int) round($this->searchResults[$key]);
                }

                return $row;
            }))
            ->map(fn (array $row) => $this->createElement($row));

        $elements = $this->afterHydrate($elements)
            ->unless($this->asArray, function (Collection $elements) {
                $dependencyCollector = app(DependencyCollector::class);

                $allElements = $elements->all();

                $elements = $elements->map(function (ElementInterface $element) use ($allElements, $dependencyCollector) {
                    // Set the full query result on the element, in case it's needed for lazy eager loading
                    $element->elementQueryResult = $allElements;

                    // If we're collecting cache info and the element is expirable, register its expiry date
                    if (
                        $element instanceof ExpirableElementInterface &&
                        $dependencyCollector->isCollecting() &&
                        ($expiryDate = $element->getExpiryDate()) !== null
                    ) {
                        $dependencyCollector->setExpiryDate($expiryDate);
                    }

                    return $element;
                });

                ElementHelper::setNextPrevOnElements($elements);

                // Should we eager-load some elements onto these?
                if ($this->with) {
                    Elements::eagerLoadElements($this->elementType, $elements->all(), $this->with);
                }

                return $elements;
            })->all();

        if ($this->withProvisionalDrafts) {
            $elements = app(Drafts::class)->withProvisionalDrafts($elements);
        }

        event($event = new ElementsHydrated($elements, $items));

        return $event->elements;
    }

    /**
     * @param  Collection<array-key, TValue>  $elements
     * @return Collection<array-key, TValue>
     */
    public function afterHydrate(Collection $elements): Collection
    {
        return $elements;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return TValue
     */
    public function createElement(array $row): ElementInterface
    {
        // Do we have a placeholder for this element?
        if (
            ! $this->ignorePlaceholders &&
            isset($row['id'], $row['siteId']) &&
            ! is_null($element = Elements::getPlaceholderElement($row['id'], $row['siteId']))
        ) {
            return $element;
        }

        /** @var class-string<ElementInterface> $class */
        $class = $this->elementType;

        // Instantiate the element
        if ($class::hasTitles()) {
            // Ensure the title is a string
            $row['title'] = (string) ($row['title'] ?? '');
        }

        // Remove the field values
        // (We'll set them after the element has been created, and we have its field layout.)
        $content = Arr::pull($row, 'content') ?? [];
        if (is_string($content)) {
            $content = Json::decode($content);
        }

        if (array_key_exists('dateDeleted', $row)) {
            $row['trashed'] = $row['dateDeleted'] !== null;
        }

        if ($this->drafts !== false) {
            $row['isProvisionalDraft'] = (bool) ($row['isProvisionalDraft'] ?? false);

            if (! empty($row['draftId'])) {
                $row['draftCreatorId'] = Arr::pull($row, 'draftCreatorId');
                $row['draftName'] = Arr::pull($row, 'draftName');
                $row['draftNotes'] = Arr::pull($row, 'draftNotes');
            } else {
                unset(
                    $row['draftCreatorId'],
                    $row['draftName'],
                    $row['draftNotes'],
                );
            }
        }

        if ($this->revisions !== false) {
            if (! empty($row['revisionId'])) {
                $row['revisionCreatorId'] = Arr::pull($row, 'revisionCreatorId');
                $row['revisionNum'] = Arr::pull($row, 'revisionNum');
                $row['revisionNotes'] = Arr::pull($row, 'revisionNotes');
            } else {
                unset(
                    $row['revisionCreatorId'],
                    $row['revisionNum'],
                    $row['revisionNotes'],
                );
            }
        }

        event($event = new ElementHydrating(row: $row, content: $content));

        /**
         * When using addSelect() to select extra columns, they might appear
         * as `table.column`. We just want `column`
         */
        $row = collect($event->row)
            ->mapWithKeys(fn (mixed $value, string $key) => [Str::after($key, '.') => $value])
            ->all();

        $element = $event->element ?? new $class($row);

        // Set the custom field values
        if (! empty($content)) {
            $fieldLayout = $element->getFieldLayout();

            if ($fieldLayout) {
                $element->setDirtyFieldTracking(false);

                foreach ($fieldLayout->getCustomFields() as $field) {
                    if ($field::dbType() !== null && isset($content[$field->layoutElement->uid])) {
                        $handle = $field->layoutElement->handle ?? $field->handle;
                        $element->setFieldValue($handle, $content[$field->layoutElement->uid]);
                    }
                }

                $element->setDirtyFieldTracking();

                $generatedFieldValues = [];

                foreach ($fieldLayout->getGeneratedFields() as $field) {
                    if (isset($content[$field['uid']])) {
                        $generatedFieldValues[$field['uid']] = $content[$field['uid']];
                        if (($field['handle'] ?? '') !== '') {
                            $generatedFieldValues[$field['handle']] = $content[$field['uid']];
                        }
                    }
                }

                $element->setGeneratedFieldValues($generatedFieldValues);
            }
        }

        event($event = new ElementHydrated($element, $row, $content));

        return $event->element;
    }
}
