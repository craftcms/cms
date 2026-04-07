<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Queries\Concerns;

use craft\base\ElementInterface;
use CraftCms\Cms\Element\Contracts\ExpirableElementInterface;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Element\Queries\Events\ElementHydrated;
use CraftCms\Cms\Element\Queries\Events\ElementsHydrated;
use CraftCms\Cms\Element\Queries\Events\HydratingElement;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\View\CacheCollectors\DependencyCollector;
use Illuminate\Support\Collection;
use stdClass;

/**
 * @template TValue
 *
 * @internal
 */
trait HydratesElements
{
    /**
     * Create a collection of elements from plain arrays.
     *
     * @return TValue[]|array<array>
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
                    Elements::eagerLoadElements($this->elementType, $elements, $this->with);
                }

                return $elements;
            })->all();

        if ($this->withProvisionalDrafts) {
            $elements = app(Drafts::class)->withProvisionalDrafts($elements);
        }

        event($event = new ElementsHydrated($elements, $items));

        return $event->elements;
    }

    public function afterHydrate(Collection $elements): Collection
    {
        return $elements;
    }

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

        // Set the field values
        $content = Arr::pull($row, 'content');
        $row['fieldValues'] = [];

        if (! empty($content) && (! empty($this->customFields) || ! empty($this->generatedFields))) {
            if (is_string($content)) {
                $content = Json::decode($content);
            }

            foreach ($this->customFields as $field) {
                if (is_null($field::dbType())) {
                    continue;
                }

                if (! isset($content[$field->layoutElement->uid])) {
                    continue;
                }

                $handle = $field->layoutElement->handle ?? $field->handle;
                $row['fieldValues'][$handle] = $content[$field->layoutElement->uid];
            }

            foreach ($this->generatedFields as $field) {
                if (! isset($content[$field['uid']])) {
                    continue;
                }

                $row['generatedFieldValues'][$field['uid']] = $content[$field['uid']];

                if (! empty($field['handle'] ?? '')) {
                    $row['generatedFieldValues'][$field['handle']] = $content[$field['uid']];
                }
            }
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

        event($event = new HydratingElement($row));

        /**
         * When using addSelect() to select extra columns, they might appear
         * as `table.column`. We just want `column`
         */
        $row = collect($event->row)
            ->mapWithKeys(fn (mixed $value, string $key) => [Str::after($key, '.') => $value])
            ->all();

        $element = $event->element ?? new $class($row);

        event($event = new ElementHydrated($element, $row));

        return $event->element;
    }
}
